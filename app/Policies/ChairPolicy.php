<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Chair;
use App\Models\User;

class ChairPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, Chair $chair): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Chair $chair): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
