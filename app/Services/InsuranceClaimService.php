<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\LedgerEntryType;
use App\Exceptions\InsuranceClaimException;
use App\Exceptions\InsuranceLedgerException;
use App\Mail\InsuranceClaimStatusChanged;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Claims workflow: submit -> review -> approve/reject -> paid. Every status change writes an
 * InsuranceClaimEvent and, for statuses the claimant cares about, queues an email to them.
 * Validation of user input (lengths, email format, uploads, rate limits) belongs to the caller.
 */
class InsuranceClaimService
{
    public function __construct(private InsuranceLedgerService $ledger) {}

    /**
     * Create a claim in status "submitted" with its first event and email the claimant.
     *
     * Example: $claims->submit($insurance, ['claimant_name' => 'Nguyễn Văn A', 'contact_email' => 'a@x.vn',
     *     'contact_phone' => null, 'amount_claimed' => '2000000', 'description' => '...', 'evidence_paths' => [...]], $request->ip());
     *
     * @param  array{claimant_name: string, contact_email: string, contact_phone?: ?string, amount_claimed: string|int|float, description: string, evidence_paths?: ?array<int, string>}  $data
     *
     * @throws InsuranceClaimException when amount_claimed is not positive.
     */
    public function submit(Insurance $insurance, array $data, ?string $ip = null): InsuranceClaim
    {
        $claimedCents = InsuranceLedgerService::toCents($data['amount_claimed']);
        if ($claimedCents <= 0) {
            throw InsuranceClaimException::invalidClaimedAmount();
        }

        $claim = DB::transaction(function () use ($insurance, $data, $claimedCents, $ip) {
            $claim = InsuranceClaim::create([
                'insurance_id' => $insurance->id,
                'claimant_name' => $data['claimant_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'amount_claimed' => InsuranceLedgerService::fromCents($claimedCents),
                'description' => $data['description'],
                'evidence_paths' => $data['evidence_paths'] ?? null,
                'status' => ClaimStatus::Submitted,
                'ip_address' => $ip,
            ]);

            $claim->events()->create(['from_status' => null, 'to_status' => ClaimStatus::Submitted]);

            return $claim;
        });

        $this->notifyClaimant($claim, ClaimStatus::Submitted);

        return $claim;
    }

    /**
     * Move a claim to another status (not "paid": use markPaid()).
     *
     * $extra keys (all optional unless stated):
     * - approved_amount: REQUIRED when approving; > 0 and <= amount_claimed.
     * - public_summary: anonymized summary shown on public pages.
     * - decision_note: internal admin note (never shown publicly or emailed).
     * Approving or rejecting sets decided_by / decided_at. $note is stored on the (admin-only) event.
     *
     * Example: $claims->transition($claim, ClaimStatus::Approved, $admin, 'Đủ bằng chứng', ['approved_amount' => '1500000', 'public_summary' => '...']);
     *
     * @param  array{approved_amount?: string|int|float, public_summary?: ?string, decision_note?: ?string}  $extra
     *
     * @throws InsuranceClaimException on a disallowed transition or invalid approved amount.
     */
    public function transition(InsuranceClaim $claim, ClaimStatus $to, User $actor, ?string $note = null, array $extra = []): InsuranceClaim
    {
        if ($to === ClaimStatus::Paid) {
            throw InsuranceClaimException::useMarkPaid();
        }

        DB::transaction(function () use ($claim, $to, $actor, $note, $extra) {
            $locked = InsuranceClaim::query()->lockForUpdate()->findOrFail($claim->getKey());
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw InsuranceClaimException::invalidTransition($from, $to);
            }

            $attributes = ['status' => $to];

            if ($to === ClaimStatus::Approved) {
                $approvedCents = array_key_exists('approved_amount', $extra)
                    ? InsuranceLedgerService::toCents($extra['approved_amount'])
                    : 0;

                if ($approvedCents <= 0 || $approvedCents > InsuranceLedgerService::toCents((string) $locked->amount_claimed)) {
                    throw InsuranceClaimException::invalidApprovedAmount();
                }

                $attributes['approved_amount'] = InsuranceLedgerService::fromCents($approvedCents);
            }

            if (in_array($to, [ClaimStatus::Approved, ClaimStatus::Rejected], true)) {
                $attributes['decided_by'] = $actor->id;
                $attributes['decided_at'] = now();
            }

            foreach (['public_summary', 'decision_note'] as $key) {
                if (array_key_exists($key, $extra)) {
                    $attributes[$key] = $extra[$key];
                }
            }

            $locked->update($attributes);
            $locked->events()->create(['from_status' => $from, 'to_status' => $to, 'note' => $note, 'actor_id' => $actor->id]);
        });

        $claim->refresh();
        $this->notifyClaimant($claim, $to);

        return $claim;
    }

    /**
     * Record that an approved claim was paid out (by an admin, outside the system): writes a
     * payout ledger entry linked to the claim, lowers the partner's balance, sets paid_at and
     * status "paid" — all in one transaction, so nothing changes if any step fails.
     *
     * Example: $claims->markPaid($claim, $admin, '1500000', 'VCB FT2611160001');
     *
     * @throws InsuranceClaimException when the claim is not approved or the amount is not in (0, approved_amount].
     * @throws InsuranceLedgerException when the partner's balance is insufficient.
     */
    public function markPaid(InsuranceClaim $claim, User $actor, string|int|float $paidAmount, ?string $reference = null): InsuranceClaim
    {
        $paidCents = InsuranceLedgerService::toCents($paidAmount);

        DB::transaction(function () use ($claim, $actor, $paidCents, $reference) {
            $locked = InsuranceClaim::query()->lockForUpdate()->findOrFail($claim->getKey());

            if (! $locked->status->canTransitionTo(ClaimStatus::Paid)) {
                throw InsuranceClaimException::invalidTransition($locked->status, ClaimStatus::Paid);
            }

            if ($paidCents <= 0 || $paidCents > InsuranceLedgerService::toCents((string) $locked->approved_amount)) {
                throw InsuranceClaimException::invalidPaidAmount();
            }

            $this->ledger->record(
                $locked->insurance,
                LedgerEntryType::Payout,
                InsuranceLedgerService::fromCents(-$paidCents),
                $actor,
                $reference,
                'Chi trả yêu cầu bồi thường #'.$locked->id,
                $locked,
            );

            $locked->update(['status' => ClaimStatus::Paid, 'paid_at' => now()]);
            $locked->events()->create([
                'from_status' => ClaimStatus::Approved,
                'to_status' => ClaimStatus::Paid,
                'note' => $reference ? 'Mã giao dịch: '.$reference : null,
                'actor_id' => $actor->id,
            ]);
        });

        $claim->refresh();
        $this->notifyClaimant($claim, ClaimStatus::Paid);

        return $claim;
    }

    private function notifyClaimant(InsuranceClaim $claim, ClaimStatus $status): void
    {
        if ($status->notifiesClaimant() && $claim->contact_email) {
            Mail::to($claim->contact_email)->queue(new InsuranceClaimStatusChanged($claim, $status));
        }
    }
}
