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
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const TIMELINE_LIMIT = 10;

    private const RELATED_LIMIT = 5;

    /** Largest whole VND amount the reports.damage_amount decimal(10,2) column can hold. */
    private const MAX_DAMAGE_AMOUNT = 99_999_999;

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

        // The form shows the amount with thousand separators ("1.500.000"); VND has no decimals.
        if (is_string($request->input('damage_amount'))) {
            $request->merge(['damage_amount' => preg_replace('/[\s.,]/', '', $request->input('damage_amount'))]);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(Report::TYPES)],
            'target_id' => 'required|string|max:255',
            'target_name' => 'nullable|string|max:255',
            // Fields of other tabs are dropped rather than rejected, so a stale value never blocks the form.
            'target_bank' => 'exclude_if:type,website|nullable|string|max:255',
            'target_socials' => 'exclude_if:type,website|nullable|string|max:255',
            'sold_account' => 'exclude_unless:type,back|nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            // Back reports get their scam type below; the other types cannot claim it.
            'scam_type' => ['exclude_if:type,back', 'nullable', Rule::enum(ScamType::class)->except(ScamType::AccountBack)],
            // integer (not numeric) so "1e3" is refused; max is the decimal(10,2) column capacity.
            'damage_amount' => 'required|integer|min:0|max:'.self::MAX_DAMAGE_AMOUNT,
            'description' => 'required|string|min:20|max:5000',
            'reporter_name' => 'nullable|string|max:255',
            'reporter_contact' => 'required|string|max:255',
            'reporter_email' => ['required', 'email:rfc', 'regex:/@[^@\s]+\.[^@\s.]+$/', 'max:255'],
            'reporter_role' => ['required', Rule::in(Report::REPORTER_ROLES)],

            'evidence_images' => 'required|array|min:1|max:10',
            'evidence_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'type.required' => 'Vui lòng chọn loại tố cáo.',
            'type.in' => 'Loại tố cáo không hợp lệ.',
            'target_id.required' => 'Vui lòng nhập số tài khoản, số điện thoại hoặc link của đối tượng.',
            'target_id.max' => 'Thông tin đối tượng không được dài quá 255 ký tự.',
            'target_socials.max' => 'Kênh liên hệ khác không được dài quá 255 ký tự.',
            'sold_account.max' => 'Tài khoản đã mua không được dài quá 255 ký tự.',
            'scam_type.enum' => 'Hình thức lừa đảo không hợp lệ.',
            'damage_amount.required' => 'Vui lòng nhập số tiền thiệt hại (nhập 0 nếu chưa mất tiền).',
            'damage_amount.integer' => 'Số tiền thiệt hại phải là chữ số.',
            'damage_amount.min' => 'Số tiền không được nhỏ hơn 0.',
            'damage_amount.max' => 'Số tiền tối đa là 99.999.999 VNĐ. Nếu thiệt hại lớn hơn, hãy ghi rõ số tiền trong phần kể lại sự việc.',
            'description.required' => 'Vui lòng kể lại sự việc.',
            'description.min' => 'Vui lòng mô tả sự việc với ít nhất 20 ký tự.',
            'description.max' => 'Phần kể lại sự việc không được dài quá 5000 ký tự.',
            'reporter_contact.required' => 'Vui lòng nhập số Zalo hoặc SĐT để kiểm duyệt viên liên hệ khi cần.',
            'reporter_contact.max' => 'Thông tin liên hệ không được dài quá 255 ký tự.',
            'reporter_email.required' => 'Vui lòng nhập email để nhận kết quả kiểm duyệt.',
            'reporter_email.email' => 'Email không đúng định dạng.',
            'reporter_email.regex' => 'Email không đúng định dạng, ví dụ: ten@gmail.com',
            'reporter_role.required' => 'Vui lòng chọn vai trò của bạn.',
            'reporter_role.in' => 'Vai trò người báo cáo không hợp lệ.',
            'evidence_images.required' => 'Bắt buộc phải tải lên ít nhất 1 ảnh bằng chứng.',
            'evidence_images.min' => 'Bắt buộc phải tải lên ít nhất 1 ảnh bằng chứng.',
            'evidence_images.max' => 'Chỉ được tải lên tối đa 10 ảnh.',
            'evidence_images.*.image' => 'File tải lên phải là hình ảnh.',
            'evidence_images.*.mimes' => 'Ảnh phải có định dạng JPG, PNG, GIF hoặc WEBP.',
            'evidence_images.*.max' => 'Mỗi hình ảnh không được vượt quá 5MB.',
        ]);

        // Acc bị back always has the same scam type, so the form does not ask for it.
        if ($validated['type'] === 'back') {
            $validated['scam_type'] = ScamType::AccountBack->value;
            $validated['category'] = ScamType::AccountBack->label();
        }

        $slug = Str::slug(($validated['target_name'] ?? 'scammer').'-'.Str::random(8));

        $report = Report::create([
            'type' => $validated['type'],
            'reporter_name' => $validated['reporter_name'] ?? 'Người dùng',
            'reporter_contact' => $validated['reporter_contact'],
            'reporter_email' => $validated['reporter_email'],
            'reporter_role' => $validated['reporter_role'],

            'target_id' => $validated['target_id'],
            'target_name' => $validated['target_name'] ?? null,
            'target_bank' => $validated['target_bank'] ?? null,
            'target_socials' => $validated['target_socials'] ?? null,
            'sold_account' => $validated['sold_account'] ?? null,
            'category' => $validated['category'] ?? null,
            'scam_type' => $validated['scam_type'] ?? null,
            'damage_amount' => $validated['damage_amount'],
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

        // Without JavaScript there is no success dialog; the form page shows the flash instead.
        return redirect()->route('reports')
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

        // Each secondary channel (Zalo number, Facebook/TikTok link) is masked on its own.
        $maskedSocials = $this->socialChannels($report)
            ->map(fn (string $channel): string => StringHelper::mask_id($channel, 'account'));
        // A game nick or profile link: masked like a link or a name, never as digits.
        $maskedSoldAccount = filled($report->sold_account) ? StringHelper::mask_id($report->sold_account, 'website') : null;

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
            'maskedSocials' => $maskedSocials,
            'maskedSoldAccount' => $maskedSoldAccount,
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
     * Secondary channels from `target_socials`, one entry per comma/semicolon/line separated value.
     *
     * @return SupportCollection<int, string>
     */
    private function socialChannels(Report $report): SupportCollection
    {
        return collect(preg_split('/[,;\n]+/', (string) $report->target_socials))
            ->map(fn (string $channel): string => trim($channel))
            ->filter()
            ->values();
    }

    /**
     * Replace the raw target id, secondary channels, sold account and name inside free text with their masked forms.
     */
    private function redactTarget(string $text, Report $report): string
    {
        $replacements = [];
        $identifiers = $this->socialChannels($report)
            ->map(fn (string $channel): array => [$channel, 'account'])
            ->prepend([trim((string) $report->target_id), $report->type]);

        foreach ($identifiers as [$raw, $type]) {
            if (mb_strlen($raw) < 4) {
                continue;
            }

            $replacements[$raw] = StringHelper::mask_id($raw, $type);

            // A profile link is often quoted as the bare handle ("nguyen.van.a") in the story.
            [$queryType, $handle] = StringHelper::detectQueryType($raw);
            if ($queryType === 'facebook' && mb_strlen($handle) >= 4) {
                $replacements[$handle] = StringHelper::mask_name($handle);
            }

            $digits = preg_replace('/\D/', '', $raw);
            if ($type !== 'website' && strlen($digits) >= 6) {
                $replacements[$digits] = StringHelper::mask_id($digits, $type);
            }
        }

        // The sold account is masked like a link or a name; its nick (last word or link segment) is often quoted alone.
        $soldAccount = trim((string) $report->sold_account);
        if (mb_strlen($soldAccount) >= 4) {
            $replacements[$soldAccount] = StringHelper::mask_id($soldAccount, 'website');

            $parts = preg_split('/[\s\/]+/u', $soldAccount, -1, PREG_SPLIT_NO_EMPTY);
            $nick = (string) end($parts);
            if (mb_strlen($nick) >= 4 && $nick !== $soldAccount) {
                $replacements[$nick] = StringHelper::mask_name($nick);
            }
        }

        // Longest first, so a short digit run never breaks up a longer raw value before it is masked.
        uksort($replacements, fn ($a, $b): int => mb_strlen((string) $b) <=> mb_strlen((string) $a));

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
