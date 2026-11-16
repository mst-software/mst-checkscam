<?php

namespace App\Services;

use App\Enums\InsuranceStatusEventType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Suspends and reinstates insurance partners, writing an InsuranceStatusEvent for each change.
 * A suspended partner drops out of Insurance::trusted() and verifies as "lapsed".
 */
class InsuranceSuspensionService
{
    public const REASON_LOW_BALANCE = 'Quỹ dưới mức tối thiểu';

    public const REASON_APPROVED_REPORT = 'Có tố cáo được duyệt nhắm vào kênh chính thức';

    /**
     * Suspend a partner. Returns false (and writes nothing) when it is already suspended.
     *
     * Example: $suspensions->suspend($insurance, 'Vi phạm quy định', $admin);
     */
    public function suspend(Insurance $insurance, string $reason, ?User $by = null): bool
    {
        if ($insurance->isSuspended()) {
            return false;
        }

        DB::transaction(function () use ($insurance, $reason, $by) {
            $insurance->forceFill(['suspended_at' => now(), 'suspension_reason' => $reason])->save();
            $insurance->statusEvents()->create([
                'type' => InsuranceStatusEventType::Suspended,
                'reason' => $reason,
                'actor_id' => $by?->id,
            ]);
        });

        return true;
    }

    /**
     * Lift a suspension. Returns false (and writes nothing) when the partner is not suspended.
     *
     * Example: $suspensions->reinstate($insurance, $admin, 'Đã xác minh tố cáo sai');
     */
    public function reinstate(Insurance $insurance, ?User $by = null, ?string $reason = null): bool
    {
        if (! $insurance->isSuspended()) {
            return false;
        }

        DB::transaction(function () use ($insurance, $by, $reason) {
            $insurance->forceFill(['suspended_at' => null, 'suspension_reason' => null])->save();
            $insurance->statusEvents()->create([
                'type' => InsuranceStatusEventType::Reinstated,
                'reason' => $reason,
                'actor_id' => $by?->id,
            ]);
        });

        return true;
    }

    /**
     * After a report is approved: suspend every trusted partner whose OFFICIAL channel is the
     * report's target, pending admin review. Never throws; returns the partners it suspended.
     *
     * @return Collection<int, Insurance>
     */
    public function suspendForApprovedReport(Report $report, ?User $by = null): Collection
    {
        try {
            $insurances = InsuranceChannel::query()
                ->official()
                ->matchingInput((string) $report->target_id)
                ->with('insurance')
                ->get()
                ->pluck('insurance')
                ->filter(fn (?Insurance $insurance) => $insurance?->isTrusted())
                ->unique('id')
                ->values();

            return $insurances->filter(fn (Insurance $insurance) => $this->suspend($insurance, self::REASON_APPROVED_REPORT, $by))->values();
        } catch (Throwable $e) {
            Log::error('Auto-suspending insurance partners for an approved report failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }
}
