<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, User $account): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->id === $account->id) {
            return true;
        }

        return $user->hasRole(UserRole::Receptionist, UserRole::Specialist)
            && in_array($account->role, [UserRole::Specialist, UserRole::ClinicalStaff], true);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, User $account): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
