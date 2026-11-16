<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Enums\LedgerEntryType;
use App\Helpers\ConfigHelper;
use App\Models\Insurance;
use App\Models\InsuranceLedgerEntry;
use App\Services\InsuranceLedgerService;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Http\Request;

class InsuranceController extends Controller
{
    /** Rows shown in the public claim history and ledger lists on a partner profile. */
    public const HISTORY_LIMIT = 10;

    /** Claim statuses whose outcome is public (masked name, amounts, admin-written summary). */
    public const PUBLIC_CLAIM_STATUSES = [ClaimStatus::Approved, ClaimStatus::Paid, ClaimStatus::Rejected];

    public function index(Request $request)
    {
        SEOTools::setTitle('Quỹ bảo hiểm uy tín - Tra cứu lừa đảo');
        SEOTools::setDescription('Danh sách giao dịch viên đã tham gia ký quỹ bảo hiểm với Scamproof, kèm trạng thái xác thực và số dư quỹ hiện tại.');

        $query = Insurance::where('status', 1);
        // ... rest of index ...
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $insurances = $query->orderBy('id', 'asc')->get();

        $total_fund = Insurance::where('status', 1)->sum('amount');
        $total_members = Insurance::trusted()->count();

        return view('insurances.index', compact('insurances', 'total_fund', 'total_members'));
    }

    public function show($slug, InsuranceLedgerService $ledger)
    {
        $insurance = Insurance::where('status', 1)
            ->where('slug', $slug)
            ->firstOrFail();

        $channels = $insurance->channels()
            ->official()
            ->orderBy('id')
            ->get(['id', 'insurance_id', 'type', 'value', 'normalized_value', 'verified_at'])
            ->groupBy(fn ($channel) => $channel->type->value);

        $claimCounts = $insurance->claims()
            ->toBase()
            ->select('status')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countOf = fn (ClaimStatus ...$statuses): int => (int) collect($statuses)->sum(fn (ClaimStatus $status) => $claimCounts[$status->value] ?? 0);

        $claimStats = [
            'total' => (int) $claimCounts->sum(),
            'approved' => $countOf(ClaimStatus::Approved, ClaimStatus::Paid),
            'rejected' => $countOf(ClaimStatus::Rejected),
            'in_progress' => $countOf(ClaimStatus::Submitted, ClaimStatus::Reviewing, ClaimStatus::NeedInfo),
            'total_paid' => abs((float) InsuranceLedgerEntry::query()
                ->where('insurance_id', $insurance->id)
                ->where('type', LedgerEntryType::Payout)
                ->sum('amount')),
        ];

        // Only public columns are loaded: contact data, description, evidence, notes and IP never reach the view.
        $publicClaims = $insurance->claims()
            ->whereIn('status', self::PUBLIC_CLAIM_STATUSES)
            ->with('payoutEntry:id,claim_id,amount')
            ->latest('decided_at')
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get(['id', 'insurance_id', 'claimant_name', 'amount_claimed', 'approved_amount', 'status', 'public_summary', 'decided_at', 'paid_at', 'created_at']);

        // Reference and note are internal: never select them for the public page.
        $ledgerEntries = InsuranceLedgerEntry::query()
            ->where('insurance_id', $insurance->id)
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get(['id', 'type', 'amount', 'balance_after', 'created_at']);

        $isReconciled = $ledgerEntries->isNotEmpty()
            && InsuranceLedgerService::toCents($ledger->balanceFromLedger($insurance)) === InsuranceLedgerService::toCents((string) $insurance->amount);

        // Advanced SEO
        $siteTitle = ConfigHelper::getConfig('site_title', 'Scamproof');
        $metaTitle = "Xác Minh Tín Nhiệm: {$insurance->full_name} | Bảo Hiểm {$siteTitle}";
        $metaDesc = "{$insurance->full_name} là giao dịch viên tham gia quỹ bảo hiểm Scamproof, số dư ký quỹ ".number_format($insurance->amount).' VNĐ. Xem trạng thái xác thực, kênh liên hệ chính thức và lịch sử bồi thường.';

        SEOTools::setTitle($metaTitle);
        SEOTools::setDescription($metaDesc);
        SEOTools::metatags()->addKeyword("bảo hiểm, tín nhiệm, {$insurance->full_name}, uy tín giao dịch, check scam, quỹ bảo hiểm mmo");
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::opengraph()->addProperty('type', 'profile');
        SEOTools::opengraph()->setTitle($metaTitle);
        SEOTools::opengraph()->setDescription($metaDesc);
        SEOTools::opengraph()->addImage($insurance->avatar_url);

        // Structured Data for Trust/Insurance Member
        SEOTools::jsonLd()->setTitle($metaTitle);
        SEOTools::jsonLd()->setDescription($metaDesc);
        SEOTools::jsonLd()->setType('ProfessionalService');
        SEOTools::jsonLd()->addImage($insurance->avatar_url);
        SEOTools::jsonLd()->addValue('name', $insurance->full_name);
        SEOTools::jsonLd()->addValue('priceRange', '$$$');
        SEOTools::jsonLd()->addValue('address', [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Vietnam',
            'addressCountry' => 'VN',
        ]);

        return view('insurances.detail', compact('insurance', 'channels', 'claimStats', 'publicClaims', 'ledgerEntries', 'isReconciled'));
    }
}
