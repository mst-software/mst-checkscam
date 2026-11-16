<?php

namespace App\Policies;

use App\Models\InsuranceClaim;
use App\Models\User;

/**
 * Staff may review claims (start review, ask for information, reject, edit the summary).
 * Approving an amount and recording a payout move money, so they are admin-only.
 */
class InsuranceClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, InsuranceClaim $claim): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, InsuranceClaim $claim): bool
    {
        return $this->isStaff($user);
    }

    public function approve(User $user, InsuranceClaim $claim): bool
    {
        return $user->isAdmin();
    }

    public function markPaid(User $user, InsuranceClaim $claim): bool
    {
        return $user->isAdmin();
    }

    private function isStaff(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
