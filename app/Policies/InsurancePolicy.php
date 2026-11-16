<?php

namespace App\Policies;

use App\Models\Insurance;
use App\Models\User;

/**
 * Staff (admins and moderators) may view partners and edit their profile and channels.
 * Anything that touches the deposit or the partner's standing is admin-only.
 */
class InsurancePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Insurance $insurance): bool
    {
        return $this->isStaff($user);
    }

    /** Creating a partner records its opening deposit in the ledger. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Insurance $insurance): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, Insurance $insurance): bool
    {
        return $user->isAdmin();
    }

    /** Record deposits, refunds and adjustments in the ledger. */
    public function manageFunds(User $user, Insurance $insurance): bool
    {
        return $user->isAdmin();
    }

    /** Suspend or reinstate the partner. */
    public function changeStanding(User $user, Insurance $insurance): bool
    {
        return $user->isAdmin();
    }

    private function isStaff(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
