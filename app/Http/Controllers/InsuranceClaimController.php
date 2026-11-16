<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\SeoHelper;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public compensation claim form against an intermediary's (GDV) deposit.
 * Review, decisions and payouts happen in the admin panel through InsuranceClaimService.
 */
class InsuranceClaimController extends Controller
{
    public const MAX_CLAIMS_PER_DAY = 3;

    public const MIN_DESCRIPTION_LENGTH = 30;

    public function __construct(private InsuranceClaimService $claims) {}

    /**
     * Public reference shown to the claimant, e.g. "YC-000123".
     */
    public static function referenceCode(InsuranceClaim $claim): string
    {
        return 'YC-'.str_pad((string) $claim->id, 6, '0', STR_PAD_LEFT);
    }

    public function create(Request $request): View
    {
        $partner = $request->filled('partner')
            ? $this->publicPartners()->where('slug', $request->string('partner')->toString())->first()
            : null;

        SeoHelper::setPage(
            'Gửi yêu cầu bồi thường',
            'Gửi yêu cầu bồi thường khi bị thiệt hại trong giao dịch qua giao dịch viên (GDV) đã ký quỹ với Scamproof; ban quản trị sẽ xem xét và phản hồi qua email.',
        );

        return view('claims.create', [
            'partner' => $partner,
            'partners' => $partner ? collect() : $this->publicPartners()->orderBy('full_name')->get(['id', 'full_name', 'slug']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real users never see this field, so pretend success without saving.
        if ($request->filled('website')) {
            return redirect()->route('insurance-claims.create')->with('claim_submitted', ['reference' => null]);
        }

        $ip = $request->ip();

        $recentClaimsCount = InsuranceClaim::query()
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentClaimsCount >= self::MAX_CLAIMS_PER_DAY) {
            return back()->withInput()->with('error', 'Bạn đã gửi quá nhiều yêu cầu bồi thường hôm nay. Vui lòng thử lại vào ngày mai!');
        }

        // Accept "2.000.000" or "2,000,000 đ": keep digits only before validating.
        if (is_string($request->input('amount_claimed'))) {
            $request->merge(['amount_claimed' => preg_replace('/\D/', '', $request->input('amount_claimed'))]);
        }

        $validated = $request->validate([
            'partner' => ['required', 'string', Rule::exists('insurances', 'slug')->where('status', 1)],
            'claimant_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'amount_claimed' => 'required|integer|min:1|max:999999999999',
            'description' => 'required|string|min:'.self::MIN_DESCRIPTION_LENGTH.'|max:5000',
            'evidence_images' => 'nullable|array|max:5',
            'evidence_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'consent' => 'accepted',
        ], [
            'partner.required' => 'Vui lòng chọn giao dịch viên.',
            'partner.exists' => 'Giao dịch viên không tồn tại.',
            'claimant_name.required' => 'Vui lòng nhập họ tên.',
            'contact_email.required' => 'Vui lòng nhập email để nhận cập nhật về yêu cầu.',
            'contact_email.email' => 'Email không hợp lệ.',
            'amount_claimed.required' => 'Vui lòng nhập số tiền yêu cầu bồi thường.',
            'amount_claimed.integer' => 'Số tiền không hợp lệ.',
            'amount_claimed.min' => 'Số tiền phải lớn hơn 0.',
            'amount_claimed.max' => 'Số tiền quá lớn.',
            'description.required' => 'Vui lòng mô tả sự việc.',
            'description.min' => 'Vui lòng mô tả sự việc với ít nhất :min ký tự.',
            'evidence_images.max' => 'Chỉ được tải lên tối đa 5 ảnh.',
            'evidence_images.*.image' => 'File tải lên phải là hình ảnh.',
            'evidence_images.*.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc GIF.',
            'evidence_images.*.max' => 'Mỗi hình ảnh không được vượt quá 5MB.',
            'consent.accepted' => 'Vui lòng xác nhận cam kết thông tin trung thực.',
        ]);

        $partner = Insurance::query()->where('status', 1)->where('slug', $validated['partner'])->firstOrFail();

        $evidencePaths = $request->hasFile('evidence_images')
            ? FileHelper::uploadMultipleImages($request->file('evidence_images'), 'insurance-claims')
            : null;

        $claim = $this->claims->submit($partner, [
            'claimant_name' => $validated['claimant_name'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'amount_claimed' => $validated['amount_claimed'],
            'description' => $validated['description'],
            'evidence_paths' => $evidencePaths,
        ], $ip);

        return redirect()->route('insurance-claims.create')->with('claim_submitted', [
            'reference' => self::referenceCode($claim),
            'partner' => $partner->full_name,
        ]);
    }

    /**
     * Partners with a public profile (status 1), including suspended or expired ones:
     * a claim may concern a transaction made before the partner lapsed.
     */
    private function publicPartners(): Builder
    {
        return Insurance::query()->where('status', 1);
    }
}
