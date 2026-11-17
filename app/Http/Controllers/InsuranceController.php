<?php

namespace App\Http\Controllers;

use App\Enums\ChannelType;
use App\Helpers\SeoHelper;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Services\InsuranceLedgerService;
use App\Services\ScamLookupService;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InsuranceController extends Controller
{
    /** Share image when a partner has no avatar (900x300 PNG in public/img). */
    public const FALLBACK_SHARE_IMAGE = 'img/scamproof-logo.png';

    public const FALLBACK_SHARE_IMAGE_WIDTH = 900;

    public const FALLBACK_SHARE_IMAGE_HEIGHT = 300;

    public function index(Request $request)
    {
        SEOTools::setTitle('Quỹ bảo hiểm uy tín - Tra cứu lừa đảo');
        SEOTools::setDescription('Danh sách giao dịch viên đã ký quỹ với Scamproof, kèm trạng thái xác thực, số dư ký quỹ và kênh liên hệ chính thức.');

        // Expired partners keep their profile page but leave the public list; suspended ones stay listed with their badge.
        $listed = fn () => Insurance::query()->where('status', 1)->notExpired();

        $query = $listed();
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $insurances = $query->orderBy('id', 'asc')->get();

        $total_fund = $listed()->sum('amount');
        $total_members = Insurance::trusted()->count();

        return view('insurances.index', compact('insurances', 'total_fund', 'total_members'));
    }

    /**
     * Public verification profile that partners share as proof of being a verified intermediary.
     * The status is computed per request, so the response must not be cached.
     */
    public function show(string $slug, InsuranceLedgerService $ledger, ScamLookupService $scamLookup): Response
    {
        $insurance = Insurance::query()
            ->where('status', 1)
            ->where('slug', $slug)
            ->with([
                'media',
                'channels' => fn ($query) => $query->official()->orderBy('id'),
            ])
            ->firstOrFail();

        $channels = $insurance->channels;
        $contactChannels = $channels
            ->reject(fn (InsuranceChannel $channel) => $channel->type === ChannelType::BankAccount)
            ->groupBy(fn (InsuranceChannel $channel) => $channel->type->value);
        $bankAccounts = $this->bankAccounts($insurance, $channels);

        $reportCounts = $scamLookup->approvedReportCounts($channels->pluck('value')->all());
        $reportedChannels = $channels
            ->filter(fn (InsuranceChannel $channel) => ($reportCounts[trim($channel->value)] ?? 0) > 0)
            ->values();

        $isReconciled = (float) $insurance->amount > 0
            && InsuranceLedgerService::toCents($ledger->balanceFromLedger($insurance)) === InsuranceLedgerService::toCents((string) $insurance->amount);

        $profileUrl = route('insurances.frontend.show', $insurance->slug);
        $this->setProfileSeo($insurance, $profileUrl);

        return response()
            ->view('insurances.detail', [
                'insurance' => $insurance,
                'contactChannels' => $contactChannels,
                'bankAccounts' => $bankAccounts,
                'channelCount' => $channels->count(),
                'reportCounts' => $reportCounts,
                'reportedChannels' => $reportedChannels,
                'totalReports' => array_sum($reportCounts),
                'isReconciled' => $isReconciled,
                'profileUrl' => $profileUrl,
                'checkedAt' => now(),
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
    }

    /**
     * Official bank account channels, with bank name and holder from the legacy payment_accounts JSON when the number matches.
     *
     * @param  Collection<int, InsuranceChannel>  $channels
     * @return Collection<int, array{channel: InsuranceChannel, bank: ?string, holder: ?string}>
     */
    private function bankAccounts(Insurance $insurance, Collection $channels): Collection
    {
        $details = collect(is_array($insurance->payment_accounts) ? $insurance->payment_accounts : [])
            ->filter(fn ($account) => is_array($account) && is_scalar($account['number'] ?? null))
            ->keyBy(fn (array $account) => InsuranceChannel::normalize(ChannelType::BankAccount, (string) $account['number']));

        return $channels
            ->filter(fn (InsuranceChannel $channel) => $channel->type === ChannelType::BankAccount)
            ->map(function (InsuranceChannel $channel) use ($details) {
                $detail = $details->get($channel->normalized_value, []);

                return [
                    'channel' => $channel,
                    'bank' => filled($detail['bank'] ?? null) ? (string) $detail['bank'] : null,
                    'holder' => filled($detail['name'] ?? null) ? (string) $detail['name'] : null,
                ];
            })
            ->values();
    }

    /**
     * Title, description, canonical, robots, Open Graph, Twitter card and JSON-LD of a partner profile.
     * Never puts phone numbers, bank accounts or the internal suspension reason in the head.
     */
    private function setProfileSeo(Insurance $insurance, string $profileUrl): void
    {
        $name = $insurance->full_name;
        $isTrusted = $insurance->isTrusted();

        $title = $isTrusted
            ? "{$name} - Giao dịch viên xác thực | ".SeoHelper::BRAND
            : "{$name} - Hồ sơ giao dịch viên | ".SeoHelper::BRAND;

        $description = "Hồ sơ giao dịch viên {$name} trên ".SeoHelper::BRAND
            .". Mã {$insurance->memberCode()} · Trạng thái: ".($isTrusted ? 'Đã xác thực' : $insurance->publicStatusLabel());
        if ($insurance->verified_at) {
            $description .= ' · Xác thực từ '.$insurance->verified_at->format('d/m/Y');
        }
        $description .= '. Đối chiếu kênh liên hệ chính thức trước khi giao dịch.';

        SEOTools::setTitle($title);
        SEOTools::setDescription($description);
        SEOTools::setCanonical($profileUrl);
        SEOTools::metatags()->setRobots($isTrusted ? 'index, follow' : 'noindex, follow');
        SEOTools::opengraph()->setUrl($profileUrl);
        SEOTools::opengraph()->setType('profile');

        [$imageUrl, $width, $height] = $this->shareImage($insurance);
        $hasAvatar = $imageUrl !== asset(self::FALLBACK_SHARE_IMAGE);
        SeoHelper::setShareImage($imageUrl, $hasAvatar ? "Ảnh đại diện của {$name}" : SeoHelper::BRAND, $width, $height);

        SEOTools::jsonLdMulti()->setType('ProfilePage');
        SEOTools::jsonLdMulti()->setUrl($profileUrl);
        SEOTools::jsonLdMulti()->addImage($imageUrl);
        SEOTools::jsonLdMulti()->addValue('dateCreated', ($insurance->verified_at ?? $insurance->created_at)?->toIso8601String());
        SEOTools::jsonLdMulti()->addValue('dateModified', $insurance->updated_at?->toIso8601String());
        SEOTools::jsonLdMulti()->addValue('mainEntity', array_filter([
            '@type' => 'Person',
            'name' => $name,
            'url' => $profileUrl,
            'image' => $hasAvatar ? $imageUrl : null,
            'identifier' => $insurance->memberCode(),
        ]));
    }

    /**
     * Absolute share image URL and its size when known: the avatar, else the brand logo.
     *
     * @return array{0: string, 1: ?int, 2: ?int}
     */
    private function shareImage(Insurance $insurance): array
    {
        if ($insurance->hasMedia('avatar')) {
            return [url($insurance->getFirstMediaUrl('avatar', 'optimized')), null, null];
        }

        if ($insurance->avatar_url) {
            $path = filter_var($insurance->avatar, FILTER_VALIDATE_URL) ? null : public_path('uploads/'.$insurance->avatar);
            $size = $path && is_file($path) ? getimagesize($path) : false;

            return [url($insurance->avatar_url), $size ? $size[0] : null, $size ? $size[1] : null];
        }

        return [asset(self::FALLBACK_SHARE_IMAGE), self::FALLBACK_SHARE_IMAGE_WIDTH, self::FALLBACK_SHARE_IMAGE_HEIGHT];
    }
}
