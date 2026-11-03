<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PatientVisit;
use App\Models\User;

class PatientVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, PatientVisit $visit): bool
    {
        return $this->viewAny($user);
    }

    public function checkIn(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function start(User $user, PatientVisit $visit): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function complete(User $user, PatientVisit $visit): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }
}
