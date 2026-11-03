<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\StaffSchedule;
use App\Models\User;

class StaffSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, StaffSchedule $schedule): bool
    {
        return $this->viewAny($user);
    }
}
