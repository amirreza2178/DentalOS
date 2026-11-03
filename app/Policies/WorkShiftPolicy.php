<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkShift;

class WorkShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function view(User $user, WorkShift $shift): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, WorkShift $shift): bool
    {
        return $this->viewAny($user);
    }
}
