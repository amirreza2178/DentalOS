<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class ReceptionDeskPolicy
{
    public function view(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function operate(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }
}
