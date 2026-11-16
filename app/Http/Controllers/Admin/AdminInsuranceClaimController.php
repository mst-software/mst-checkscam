<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Exceptions\InsuranceClaimException;
use App\Exceptions\InsuranceLedgerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InsuranceRequest;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Claims inbox. Every status change goes through InsuranceClaimService, which checks the allowed
 * transitions under a row lock (so a double submit fails cleanly) and emails the claimant.
 * Notes typed here are internal: the claimant mail never includes them.
 */
class AdminInsuranceClaimController extends Controller
{
    public function __construct(private InsuranceClaimService $claims) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', InsuranceClaim::class);

        $status = ClaimStatus::tryFrom($request->string('status')->toString());
        $insuranceId = $request->integer('insurance_id') ?: null;
        $from = $this->parseDate($request->string('from')->toString());
        $to = $this->parseDate($request->string('to')->toString());

        $claims = InsuranceClaim::query()
            ->with('insurance')
            ->when($status, fn ($query) => $query->ofStatus($status))
            ->when($insuranceId, fn ($query) => $query->where('insurance_id', $insuranceId))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to->endOfDay()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = InsuranceClaim::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $openCount = InsuranceClaim::query()->awaitingAction()->count();
        $partners = Insurance::query()->whereHas('claims')->orderBy('full_name')->get(['id', 'full_name']);

        return view('admin.insurance-claims.index', compact(
            'claims',
            'status',
            'insuranceId',
            'from',
            'to',
            'statusCounts',
            'openCount',
            'partners',
        ));
    }

    public function show(InsuranceClaim $claim): View
    {
        Gate::authorize('view', $claim);

        $claim->load(['insurance', 'decider', 'payoutEntry', 'events.actor']);

        return view('admin.insurance-claims.show', compact('claim'));
    }

    public function startReview(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        Gate::authorize('update', $claim);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ], $this->noteMessages());

        return $this->transition($request, $claim, ClaimStatus::Reviewing, $validated['note'] ?? null);
    }

    public function requestInfo(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        Gate::authorize('update', $claim);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ], $this->noteMessages() + [
            'note.required' => 'Vui lòng ghi rõ cần bổ sung thông tin gì (ghi chú nội bộ).',
        ]);

        return $this->transition($request, $claim, ClaimStatus::NeedInfo, $validated['note']);
    }

    public function approve(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        Gate::authorize('approve', $claim);

        $validated = $request->validate([
            'approved_amount' => ['required', 'integer', 'min:1', 'max:'.InsuranceRequest::MAX_AMOUNT],
            'public_summary' => ['nullable', 'string', 'max:2000'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'approved_amount.required' => 'Vui lòng nhập số tiền duyệt bồi thường.',
            'approved_amount.integer' => 'Số tiền duyệt phải là số nguyên (VND).',
            'approved_amount.min' => 'Số tiền duyệt phải lớn hơn 0.',
            'approved_amount.max' => 'Số tiền duyệt quá lớn.',
            'public_summary.max' => 'Tóm tắt công khai tối đa :max ký tự.',
            'decision_note.max' => 'Ghi chú quyết định tối đa :max ký tự.',
        ]);

        return $this->transition($request, $claim, ClaimStatus::Approved, $validated['decision_note'] ?? null, array_filter([
            'approved_amount' => (string) $validated['approved_amount'],
            'public_summary' => $validated['public_summary'] ?? null,
            'decision_note' => $validated['decision_note'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function reject(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        Gate::authorize('update', $claim);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'public_summary' => ['nullable', 'string', 'max:2000'],
        ], [
            'reason.required' => 'Vui lòng ghi lý do từ chối.',
            'reason.max' => 'Lý do tối đa :max ký tự.',
            'public_summary.max' => 'Tóm tắt công khai tối đa :max ký tự.',
        ]);

        return $this->transition($request, $claim, ClaimStatus::Rejected, $validated['reason'], array_filter([
            'decision_note' => $validated['reason'],
            'public_summary' => $validated['public_summary'] ?? null,
        ], fn ($value) => $value !== null));
    }

    /**
     * Record that an admin paid the approved claim from the partner's deposit (outside the system).
     */
    public function markPaid(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        Gate::authorize('markPaid', $claim);

        $validated = $request->validate([
            'paid_amount' => ['required', 'integer', 'min:1', 'max:'.InsuranceRequest::MAX_AMOUNT],
            'reference' => ['required', 'string', 'max:255'],
        ], [
            'paid_amount.required' => 'Vui lòng nhập số tiền đã chi trả.',
            'paid_amount.integer' => 'Số tiền chi trả phải là số nguyên (VND).',
            'paid_amount.min' => 'Số tiền chi trả phải lớn hơn 0.',
            'paid_amount.max' => 'Số tiền chi trả quá lớn.',
            'reference.required' => 'Vui lòng nhập mã giao dịch ngân hàng.',
            'reference.max' => 'Mã giao dịch tối đa :max ký tự.',
        ]);

        try {
            $claim = $this->claims->markPaid($claim, $request->user(), (string) $validated['paid_amount'], $validated['reference']);
        } catch (InsuranceClaimException|InsuranceLedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $balance = $claim->payoutEntry?->balance_after;

        return back()->with('success', 'Đã ghi nhận chi trả và trừ quỹ của thành viên. Số dư còn lại: '
            .number_format((float) $balance, 0, ',', '.').' đ.');
    }

    /**
     * Edit the public summary and internal decision note without changing the status.
     */
    public function updateSummary(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        Gate::authorize('update', $claim);

        $validated = $request->validate([
            'public_summary' => ['nullable', 'string', 'max:2000'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'public_summary.max' => 'Tóm tắt công khai tối đa :max ký tự.',
            'decision_note.max' => 'Ghi chú quyết định tối đa :max ký tự.',
        ]);

        $claim->update([
            'public_summary' => $validated['public_summary'] ?? null,
            'decision_note' => $validated['decision_note'] ?? null,
        ]);

        return back()->with('success', 'Đã lưu tóm tắt và ghi chú.');
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function transition(Request $request, InsuranceClaim $claim, ClaimStatus $to, ?string $note, array $extra = []): RedirectResponse
    {
        try {
            $this->claims->transition($claim, $to, $request->user(), $note, $extra);
        } catch (InsuranceClaimException|InsuranceLedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã chuyển yêu cầu sang "'.$to->label().'".');
    }

    /**
     * @return array<string, string>
     */
    private function noteMessages(): array
    {
        return ['note.max' => 'Ghi chú tối đa :max ký tự.'];
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
