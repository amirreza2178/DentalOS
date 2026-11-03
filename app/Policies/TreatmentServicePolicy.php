<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TreatmentService;
use App\Models\User;

class TreatmentServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, TreatmentService $service): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, TreatmentService $service): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
