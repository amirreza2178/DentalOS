<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class ClinicSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
