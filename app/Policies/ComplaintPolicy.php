<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Complaint $complaint): bool
    {
        return $this->isStaff($user);
    }

    private function isStaff(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
