<?php

namespace App\Services;

use App\Enums\IntermediaryVerificationState;
use App\Models\Insurance;
use App\Models\InsuranceChannel;

/**
 * Answers "is this phone / bank account / Facebook / Telegram / Zalo / website really an official
 * channel of a Scamproof intermediary (GDV)?" to fight impersonation of partners and admins.
 */
class IntermediaryVerifier
{
    public function __construct(private ScamLookupService $scamLookup) {}

    /**
     * Example: $result = $verifier->verify('@gdv_hung'); $result->state; $result->message(); $result->partnerSummary();
     *
     * @param  int|null  $reportedCount  Pass when already known (e.g. from ScamLookupService::lookup) to skip the count query.
     */
    public function verify(string $input, ?int $reportedCount = null): IntermediaryVerification
    {
        $input = trim($input);

        $channels = InsuranceChannel::query()
            ->official()
            ->matchingInput($input)
            ->with('insurance')
            ->orderBy('id')
            ->get()
            ->filter(fn (InsuranceChannel $channel) => $channel->insurance !== null);

        $reportedCount ??= $input === '' ? 0 : $this->scamLookup->reportsQuery($input)->count();

        $trusted = $channels->first(fn (InsuranceChannel $channel) => $channel->insurance->isTrusted());

        if ($trusted) {
            return new IntermediaryVerification(IntermediaryVerificationState::Verified, $input, $trusted->insurance, $trusted, null, $reportedCount);
        }

        $lapsed = $channels->first();

        if ($lapsed) {
            return new IntermediaryVerification(
                IntermediaryVerificationState::Lapsed,
                $input,
                $lapsed->insurance,
                $lapsed,
                $this->lapsedReason($lapsed->insurance),
                $reportedCount,
            );
        }

        return new IntermediaryVerification(IntermediaryVerificationState::NotListed, $input, null, null, null, $reportedCount);
    }

    private function lapsedReason(Insurance $insurance): string
    {
        return match (true) {
            $insurance->isSuspended() => IntermediaryVerification::LAPSED_SUSPENDED,
            ! $insurance->isActive() => IntermediaryVerification::LAPSED_INACTIVE,
            default => IntermediaryVerification::LAPSED_EXPIRED,
        };
    }
}
