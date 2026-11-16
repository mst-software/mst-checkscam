<?php

namespace App\Http\Controllers;

use App\Enums\ScamType;
use App\Helpers\ConfigHelper;
use App\Helpers\FileHelper;
use App\Helpers\OgImageHelper;
use App\Helpers\StringHelper;
use App\Models\Comment;
use App\Models\Report;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const TIMELINE_LIMIT = 10;

    private const RELATED_LIMIT = 5;

    public function store(Request $request)
    {
        $ip = $request->ip();

        $recentReportsCount = Report::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentReportsCount >= 3) {
            if ($request->ajax()) {
                return response()->json([
                    'errors' => ['general' => ['Bạn đã gửi quá nhiều lần hôm nay. Vui lòng thử lại vào ngày mai!']],
                ], 422);
            }

            return back()->withInput()->with('error', 'Bạn đã gửi quá nhiều lần hôm nay.');
        }

        $validated = $request->validate([
            'type' => 'required|in:account,website',
            'target_id' => 'required|string|max:255',
            'target_name' => 'nullable|string|max:255',
            'target_bank' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'scam_type' => ['nullable', Rule::enum(ScamType::class)],
            'damage_amount' => 'nullable|numeric|min:0',
            'description' => 'required|string|min:50',
            'reporter_name' => 'nullable|string|max:255',
            'reporter_contact' => 'nullable|string|max:255',

            'evidence_images' => 'required|array|min:1',
            'evidence_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ], [
            'scam_type.enum' => 'Hình thức lừa đảo không hợp lệ.',
            'damage_amount.numeric' => 'Số tiền thiệt hại phải là chữ số.',
            'damage_amount.min' => 'Số tiền không được nhỏ hơn 0.',
            'description.min' => 'Vui lòng mô tả hành vi lừa đảo với ít nhất 50 ký tự.',
            'evidence_images.required' => 'Bắt buộc phải tải lên ít nhất 1 ảnh bằng chứng.',
            'evidence_images.min' => 'Bắt buộc phải tải lên ít nhất 1 ảnh bằng chứng.',
            'evidence_images.*.image' => 'File tải lên phải là hình ảnh.',
            'evidence_images.*.max' => 'Mỗi hình ảnh không được vượt quá 5MB.',
        ]);

        $slug = Str::slug(($validated['target_name'] ?? 'scammer').'-'.Str::random(8));

        $report = Report::create([
            'type' => $validated['type'],
            'reporter_name' => $validated['reporter_name'] ?? 'Người dùng',
            'reporter_contact' => $validated['reporter_contact'] ?? '',

            'target_id' => $validated['target_id'],
            'target_name' => $validated['target_name'] ?? null,
            'target_bank' => $validated['target_bank'] ?? null,
            'category' => $validated['category'] ?? null,
            'scam_type' => $validated['scam_type'] ?? null,
            'damage_amount' => $validated['damage_amount'] ?? null,
            'description' => $validated['description'],
            'status' => 'pending',
            'ip_address' => $ip,
            'slug' => $slug,
        ]);

        if ($request->hasFile('evidence_images')) {
            $paths = FileHelper::uploadMultipleImages($request->file('evidence_images'), 'reports');
            $report->update(['evidence_images' => $paths]);
        }

        if ($request->ajax()) {
            return response()->json(['redirect' => route('home')]);
        }

        return redirect()->route('home')
            ->with('success', 'Báo cáo đã được gửi và đang chờ kiểm duyệt.');
    }

    public function show(Request $request, string $slug): View
    {
        $report = Report::query()
            ->approved()
            ->where('slug', $slug)
            ->firstOrFail();

        $viewCacheKey = 'view_'.$report->id.'_'.$request->ip();
        if (! Cache::has($viewCacheKey)) {
            $report->incrementViewCount();
            Cache::put($viewCacheKey, true, now()->addHours(24));
        }

        $maskedTarget = StringHelper::mask_id($report->target_id, $report->type);
        $maskedName = StringHelper::mask_name($report->target_name);
        $redact = fn (?string $text): string => $this->redactTarget((string) $text, $report);

        $targetStats = Report::query()
            ->approved()
            ->where('target_id', $report->target_id)
            ->selectRaw('COUNT(*) as reports_count, MIN(created_at) as first_reported_at, MAX(created_at) as last_reported_at')
            ->toBase()
            ->first();

        $reportsCount = max(1, (int) $targetStats->reports_count);
        $firstReportedAt = $targetStats->first_reported_at ? Carbon::parse($targetStats->first_reported_at) : $report->created_at;
        $lastReportedAt = $targetStats->last_reported_at ? Carbon::parse($targetStats->last_reported_at) : $report->created_at;

        $targetReports = $reportsCount > 1
            ? Report::query()
                ->approved()
                ->where('target_id', $report->target_id)
                ->latest()
                ->limit(self::TIMELINE_LIMIT)
                ->get(['id', 'slug', 'type', 'scam_type', 'category', 'damage_amount', 'created_at'])
            : collect();

        $relatedReports = $this->relatedReports($report);

        $comments = $report->comments()->get();
        $comments->each(fn (Comment $comment) => $comment->setAttribute('display_content', $redact($comment->content)));

        $evidenceImages = collect($report->evidence_images ?? [])
            ->filter(fn ($image): bool => is_string($image) && $image !== '')
            ->map(fn (string $image): string => filter_var($image, FILTER_VALIDATE_URL) ? $image : asset('uploads/'.$image))
            ->values();

        $scamTypeLabel = $report->scam_type?->label();
        $description = $redact($report->description);

        $this->setReportSeo($report, $maskedTarget, $maskedName, $scamTypeLabel, $description, $reportsCount);

        return view('scammer.index', [
            'report' => $report,
            'maskedTarget' => $maskedTarget,
            'maskedName' => $maskedName,
            'scamTypeLabel' => $scamTypeLabel,
            'description' => $description,
            'reportsCount' => $reportsCount,
            'firstReportedAt' => $firstReportedAt,
            'lastReportedAt' => $lastReportedAt,
            'targetReports' => $targetReports,
            'relatedReports' => $relatedReports,
            'comments' => $comments,
            'evidenceImages' => $evidenceImages,
        ]);
    }

    /**
     * Latest approved warnings with the same scam type or bank, excluding this target.
     *
     * @return Collection<int, Report>
     */
    private function relatedReports(Report $report): Collection
    {
        return Report::query()
            ->approved()
            ->whereKeyNot($report->id)
            ->where('target_id', '!=', $report->target_id)
            ->where(function (Builder $query) use ($report) {
                if ($report->scam_type === null && empty($report->target_bank)) {
                    $query->where('type', $report->type);

                    return;
                }

                if ($report->scam_type !== null) {
                    $query->where('scam_type', $report->scam_type);
                }

                if (! empty($report->target_bank)) {
                    $query->orWhere('target_bank', $report->target_bank);
                }
            })
            ->latest()
            ->limit(self::RELATED_LIMIT)
            ->get(['id', 'slug', 'type', 'target_id', 'target_name', 'scam_type', 'created_at']);
    }

    /**
     * Replace the raw target id and name inside free text with their masked forms.
     */
    private function redactTarget(string $text, Report $report): string
    {
        $replacements = [];
        $targetId = trim((string) $report->target_id);

        if (mb_strlen($targetId) >= 4) {
            $replacements[$targetId] = StringHelper::mask_id($targetId, $report->type);

            $digits = preg_replace('/\D/', '', $targetId);
            if ($report->type !== 'website' && strlen($digits) >= 6) {
                $replacements[$digits] = StringHelper::mask_id($digits, $report->type);
            }
        }

        foreach ($replacements as $raw => $masked) {
            $text = str_ireplace((string) $raw, $masked, $text);
        }

        $targetName = trim((string) $report->target_name);
        if (mb_strlen($targetName) >= 4) {
            $text = preg_replace('/'.preg_quote($targetName, '/').'/iu', StringHelper::mask_name($targetName), $text) ?? $text;
        }

        return $text;
    }

    private function setReportSeo(Report $report, string $maskedTarget, string $maskedName, ?string $scamTypeLabel, string $description, int $reportsCount): void
    {
        $siteTitle = ConfigHelper::getConfig('site_title', 'Scamproof');
        $reportedOn = $report->created_at->format('d/m/Y');
        $scamLabel = $scamTypeLabel ?? 'Lừa đảo';
        $targetLabel = $report->type === 'website' ? 'website' : 'tài khoản';

        $metaTitle = "Cảnh báo {$maskedTarget} - {$scamLabel} ({$reportedOn}) | {$siteTitle}";
        $metaDesc = "Cảnh báo {$targetLabel} {$maskedTarget} ({$maskedName}) bị tố cáo: ".mb_strtolower($scamLabel)
            .". {$reportsCount} tố cáo đã kiểm duyệt, ngày {$reportedOn}. ".Str::limit($description, 110);

        SEOTools::setTitle($metaTitle);
        SEOTools::setDescription($metaDesc);
        SEOTools::metatags()->addKeyword([$maskedTarget, $scamLabel, 'lừa đảo', 'cảnh báo lừa đảo', 'check scam']);
        SEOTools::opengraph()->addProperty('type', 'article');

        // Share card with masked data instead of evidence screenshots, which may expose PII
        $shareImage = route('og.report', $report->slug);
        SEOTools::opengraph()->addImage($shareImage, [
            'width' => OgImageHelper::WIDTH,
            'height' => OgImageHelper::HEIGHT,
            'type' => 'image/png',
        ]);

        $logo = (string) ConfigHelper::getConfig('logo');

        SEOTools::jsonLdMulti()->setType('Article');
        SEOTools::jsonLdMulti()->addImage($shareImage);
        SEOTools::jsonLdMulti()->addValue('headline', "Cảnh báo lừa đảo {$maskedTarget} - {$scamLabel}");
        SEOTools::jsonLdMulti()->addValue('datePublished', $report->created_at->toIso8601String());
        SEOTools::jsonLdMulti()->addValue('dateModified', $report->updated_at?->toIso8601String() ?? $report->created_at->toIso8601String());
        SEOTools::jsonLdMulti()->addValue('author', ['@type' => 'Organization', 'name' => $siteTitle]);
        SEOTools::jsonLdMulti()->addValue('publisher', [
            '@type' => 'Organization',
            'name' => $siteTitle,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => filter_var($logo, FILTER_VALIDATE_URL) ? $logo : asset('uploads/'.$logo),
            ],
        ]);
    }
}
