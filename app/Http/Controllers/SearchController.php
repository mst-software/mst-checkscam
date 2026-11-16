<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Helpers\StringHelper;
use App\Models\Comment;
use App\Models\Insurance;
use App\Models\Report;
use App\Models\SearchLog;
use App\Services\ScamLookupService;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SearchController extends Controller
{
    private const AUTOCOMPLETE_MIN_LENGTH = 3;

    private const AUTOCOMPLETE_MAX_LENGTH = 100;

    /** Autocomplete requests allowed per IP per minute, so the endpoint cannot be used to enumerate targets. */
    private const AUTOCOMPLETE_PER_MINUTE = 30;

    public function __construct(private ScamLookupService $scamLookup) {}

    public function index(Request $request)
    {
        $query = trim($request->query('q', ''));

        if ($query !== '') {
            $metaTitle = "Kết quả check scam: {$query} | Tra cứu lừa đảo";
            $metaDesc = "Dữ liệu mới nhất về {$query}. Xem đối tượng này có trong danh sách đen lừa đảo hay không. Tra cứu STK, SĐT, Link FB tại Scamproof.";
            SEOTools::setTitle($metaTitle);
            SEOTools::setDescription($metaDesc);
            SEOTools::opengraph()->setUrl(url()->current());
            SEOTools::metatags()->setCanonical(url()->current());
        } else {
            SEOTools::setTitle('Tìm kiếm đối tượng lừa đảo - Scamproof');
            SEOTools::metatags()->setRobots('noindex, follow');
        }

        $results = collect();
        $matchedInsurances = collect();
        $isFound = false;
        $type = null;

        if ($query !== '') {
            [$type] = StringHelper::detectQueryType($query);

            $dbQuery = $this->scamLookup->reportsQuery($query)->with('comments');

            $matchedIds = (clone $dbQuery)->pluck('id');
            $ip = $request->ip();
            $searchCacheKey = 'search_'.md5($query).'_'.$ip;

            if ($matchedIds->isNotEmpty() && ! Cache::has($searchCacheKey)) {
                Report::whereIn('id', $matchedIds)->increment('search_count');
                Cache::put($searchCacheKey, true, now()->addHours(24));
            }

            $matchedInsurances = $this->scamLookup->matchingInsurances($query);

            $results = $dbQuery->paginate(10)->withQueryString();
            $isFound = $results->total() > 0 || $matchedInsurances->isNotEmpty();

            $alreadyLogged = SearchLog::where('search_query', $query)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->subMinute())
                ->exists();

            if (! $alreadyLogged) {
                SearchLog::create([
                    'search_query' => $query,
                    'is_found' => $isFound,
                    'ip_address' => $ip,
                ]);
            }
        }

        $recentSearches = SearchLog::where('ip_address', $request->ip())
            ->orderByDesc('created_at')
            ->limit(10)
            ->pluck('search_query')
            ->unique()
            ->values();

        $stats = [
            'total_reports' => Report::where('status', 'approved')->count(),
            'total_account' => Report::where('status', 'approved')->where('type', 'account')->count(),
            'total_website' => Report::where('status', 'approved')->where('type', 'website')->count(),
        ];

        $topWeeklyReports = StatsHelper::getTopWeeklyReports();
        $topDailySearches = StatsHelper::getTopDailySearches();

        $page = $request->input('page', 1);
        if ($page == 1) {
            $perPage = 8;
            $offset = 0;
        } else {
            $perPage = 16;
            $offset = 8 + ($page - 2) * 16;
        }

        $commentsQuery = Comment::whereHas('report', function ($query) {
            $query->where('status', 'approved');
        })->with('report')->latest();

        $totalCommentsCount = $commentsQuery->count();
        $commentItems = $commentsQuery->skip($offset)->take($perPage)->get();

        $comments = new LengthAwarePaginator(
            $commentItems,
            $totalCommentsCount,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('home', compact(
            'query',
            'results',
            'matchedInsurances',
            'isFound',
            'type',
            'recentSearches',
            'stats',
            'topWeeklyReports',
            'topDailySearches',
            'comments',
        ));
    }

    /**
     * Suggestions for the hero search box.
     *
     * Each item is {label, display, query, url, type, target_name}. Report identifiers are only
     * returned masked (`display`, via StringHelper::mask_id), so partial typing never reveals someone's
     * full phone, bank account or Facebook id. A report suggestion links to its public (masked) report
     * page through `url`; `query` is the visitor's own text, used when there is no `url`. Insurance
     * partners are public, so their `query` is the partner name and `url` is null.
     */
    public function autoComplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $length = mb_strlen($query);

        if ($length < self::AUTOCOMPLETE_MIN_LENGTH || $length > self::AUTOCOMPLETE_MAX_LENGTH) {
            return response()->json([]);
        }

        $limiterKey = 'search-autocomplete:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::AUTOCOMPLETE_PER_MINUTE)) {
            return response()->json([], 429, ['Retry-After' => RateLimiter::availableIn($limiterKey)]);
        }

        RateLimiter::hit($limiterKey, 60);

        [, $formattedQuery] = StringHelper::detectQueryType($query);
        $isNumeric = preg_match('/^\d+$/', $formattedQuery) === 1;

        if ($isNumeric && strlen($formattedQuery) < self::AUTOCOMPLETE_MIN_LENGTH) {
            return response()->json([]);
        }

        $idPattern = '%'.$this->escapeLike($formattedQuery).'%';
        $namePattern = '%'.$this->escapeLike($query).'%';

        $reportSuggestions = Report::where('status', 'approved')
            ->where(function ($q) use ($isNumeric, $formattedQuery, $idPattern, $namePattern) {
                if ($isNumeric) {
                    // Match regardless of the separators stored in target_id (e.g. "0912.345.678").
                    $q->whereRaw("REGEXP_REPLACE(target_id, '[^0-9]', '') LIKE ?", ['%'.$formattedQuery.'%']);
                } else {
                    $q->where('target_id', 'LIKE', $idPattern);
                }

                $q->orWhere('target_name', 'LIKE', $namePattern);
            })
            ->select('target_id', 'target_name', 'type', 'slug')
            ->latest()
            ->limit(30)
            ->get()
            ->map(function (Report $report) use ($query) {
                $display = StringHelper::mask_id($report->target_id, $report->type);
                $maskedName = StringHelper::mask_name($report->target_name);

                return [
                    'label' => $display.' - '.$maskedName,
                    'display' => $display,
                    'query' => $query,
                    'url' => route('scammer.show', $report->slug),
                    'type' => $report->type,
                    'target_name' => $maskedName,
                ];
            })
            ->unique('label')
            ->take(8);

        $insuranceSuggestions = Insurance::where('status', 1)
            ->where(function ($q) use ($idPattern, $namePattern) {
                $q->where('contact_info', 'LIKE', $idPattern)
                    ->orWhere('payment_accounts', 'LIKE', $idPattern)
                    ->orWhere('full_name', 'LIKE', $namePattern);
            })
            ->select('full_name')
            ->limit(30)
            ->get()
            ->unique('full_name')
            ->take(4)
            ->map(fn (Insurance $insurance) => [
                'label' => 'Uy tín: '.$insurance->full_name,
                'display' => $insurance->full_name,
                'query' => $insurance->full_name,
                'url' => null,
                'type' => 'insurance',
                'target_name' => $insurance->full_name,
            ]);

        return response()->json($insuranceSuggestions->concat($reportSuggestions)->take(8)->values());
    }

    /**
     * Escape LIKE wildcards so "%" or "_" typed by a visitor cannot match every row.
     */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    public function clearHistory(Request $request)
    {
        SearchLog::where('ip_address', $request->ip())->delete();

        return back()->with('success', 'Đã xóa lịch sử tìm kiếm.');
    }
}
