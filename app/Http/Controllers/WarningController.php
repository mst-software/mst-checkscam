<?php

namespace App\Http\Controllers;

use App\Helpers\ConfigHelper;
use App\Helpers\StringHelper;
use App\Models\Report;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WarningController extends Controller
{
    public const PER_PAGE = 20;

    public const TYPES = [
        'all' => 'Tất cả',
        'bank' => 'Ngân hàng',
        'phone' => 'Số điện thoại',
        'facebook' => 'Facebook',
    ];

    public const SORTS = [
        'newest' => 'Mới nhất',
        'views' => 'Xem nhiều nhất',
        'comments' => 'Bình luận nhiều nhất',
    ];

    public const PERIODS = [
        7 => '7 ngày qua',
        30 => '30 ngày qua',
        90 => '90 ngày qua',
    ];

    /**
     * Derives the displayed warning type from existing columns: `reports.type` only stores
     * account|website, so phone vs bank and Facebook links are told apart by their values.
     */
    private const WARNING_TYPE_SQL = "CASE
        WHEN target_id LIKE '%facebook.com%' OR target_id LIKE '%fb.com%' THEN 'facebook'
        WHEN type = 'account' AND COALESCE(target_bank, '') = ''
            AND REGEXP_REPLACE(target_id, '[^0-9]', '') REGEXP '^(0|84)[0-9]{9}$' THEN 'phone'
        WHEN type = 'account' THEN 'bank'
        ELSE 'website'
    END";

    public function index(Request $request): View
    {
        $bankOptions = $this->bankOptions();
        $filters = $this->resolveFilters($request, $bankOptions);

        $reports = Report::query()
            ->approved()
            ->select(['id', 'type', 'target_id', 'target_name', 'target_bank', 'category', 'view_count', 'slug', 'created_at'])
            ->selectRaw(self::WARNING_TYPE_SQL.' AS warning_type')
            ->withCount('comments')
            ->when($filters['type'] !== 'all', function (Builder $query) use ($filters) {
                $query->whereRaw('('.self::WARNING_TYPE_SQL.') = ?', [$filters['type']]);
            })
            ->when($filters['bank'] !== '', fn (Builder $query) => $query->where('target_bank', $filters['bank']))
            ->when($filters['period'] !== null, fn (Builder $query) => $query->where('created_at', '>=', now()->subDays($filters['period'])))
            ->when($filters['q'] !== '', fn (Builder $query) => $this->applyKeyword($query, $filters['q']))
            ->when($filters['sort'] === 'views', fn (Builder $query) => $query->orderByDesc('view_count'))
            ->when($filters['sort'] === 'comments', fn (Builder $query) => $query->orderByDesc('comments_count'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Pages past the end are not real listings: 404 instead of an indexable empty page.
        if ($reports->currentPage() > 1 && $reports->currentPage() > $reports->lastPage()) {
            abort(404);
        }

        $hasFilters = $filters['type'] !== 'all'
            || $filters['bank'] !== ''
            || $filters['period'] !== null
            || $filters['q'] !== ''
            || $filters['sort'] !== 'newest';

        $this->setSeo($hasFilters, $reports->currentPage(), $reports->isEmpty());

        return view('warnings.index', [
            'reports' => $reports,
            'filters' => $filters,
            'hasFilters' => $hasFilters,
            'bankOptions' => $bankOptions,
            'typeOptions' => self::TYPES,
            'sortOptions' => self::SORTS,
            'periodOptions' => self::PERIODS,
        ]);
    }

    /**
     * Whitelists every GET filter; unknown values silently fall back to the defaults.
     *
     * @param  Collection<int, string>  $bankOptions
     * @return array{q: string, type: string, sort: string, period: int|null, bank: string}
     */
    private function resolveFilters(Request $request, Collection $bankOptions): array
    {
        $type = (string) $request->query('type', 'all');
        $sort = (string) $request->query('sort', 'newest');
        $period = (int) $request->query('period', 0);
        $bank = trim((string) $request->query('bank', ''));

        return [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
            'type' => array_key_exists($type, self::TYPES) ? $type : 'all',
            'sort' => array_key_exists($sort, self::SORTS) ? $sort : 'newest',
            'period' => array_key_exists($period, self::PERIODS) ? $period : null,
            'bank' => $bankOptions->contains($bank) ? $bank : '',
        ];
    }

    /**
     * Matches the keyword with the same normalization as SearchController
     * (StringHelper::detectQueryType + REGEXP_REPLACE on target_id).
     */
    private function applyKeyword(Builder $query, string $keyword): void
    {
        [$queryType, $formattedQuery] = StringHelper::detectQueryType($keyword);

        match ($queryType) {
            'phone' => $query->whereRaw(
                "REGEXP_REPLACE(target_id, '[^0-9]', '') IN (?, ?)",
                [$formattedQuery, '84'.substr($formattedQuery, 1)],
            ),
            'bank' => $query->whereRaw("REGEXP_REPLACE(target_id, '[^0-9]', '') = ?", [$formattedQuery]),
            'facebook' => $query->where(function (Builder $sub) use ($formattedQuery, $keyword) {
                $sub->where('target_id', 'LIKE', $this->likePattern($formattedQuery))
                    ->orWhere('slug', 'LIKE', $this->likePattern($formattedQuery))
                    ->orWhere('target_name', 'LIKE', $this->likePattern($keyword));
            }),
            'uuid' => $query->where('slug', $formattedQuery),
            default => $query->where(function (Builder $sub) use ($keyword) {
                $sub->whereRaw(
                    "LOWER(TRIM(REGEXP_REPLACE(target_name, '\\\\s+', ' '))) LIKE ?",
                    [$this->likePattern(StringHelper::normalizeString($keyword))],
                )->orWhere('target_id', 'LIKE', $this->likePattern($keyword));
            }),
        };
    }

    private function likePattern(string $value): string
    {
        return '%'.addcslashes($value, '%_\\').'%';
    }

    /**
     * @return Collection<int, string>
     */
    private function bankOptions(): Collection
    {
        return Report::query()
            ->approved()
            ->whereNotNull('target_bank')
            ->where('target_bank', '!=', '')
            ->distinct()
            ->orderBy('target_bank')
            ->limit(100)
            ->pluck('target_bank');
    }

    private function setSeo(bool $hasFilters, int $page, bool $isEmpty): void
    {
        $siteTitle = ConfigHelper::getConfig('site_title', 'Scamproof');

        SEOTools::setTitle("Danh sách cảnh báo lừa đảo mới nhất | {$siteTitle}");
        SEOTools::setDescription('Danh sách tài khoản ngân hàng, số điện thoại và Facebook đã bị cộng đồng tố cáo lừa đảo và được kiểm duyệt. Lọc theo loại, ngân hàng và thời gian.');

        $canonical = $hasFilters || $page <= 1
            ? route('warnings.index')
            : route('warnings.index', ['page' => $page]);

        SEOTools::metatags()->setCanonical($canonical);
        SEOTools::opengraph()->setUrl($canonical);
        SEOTools::jsonLd()->setUrl($canonical);

        if ($hasFilters || $isEmpty) {
            SEOTools::metatags()->setRobots('noindex, follow');
        }
    }
}
