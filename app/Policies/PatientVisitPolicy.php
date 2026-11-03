<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PatientVisit;
use App\Models\User;
use App\Support\SpecialistPatientScope;

class PatientVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, PatientVisit $visit): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            return true;
        }

        if ($user->hasRole(UserRole::Specialist)) {
            return SpecialistPatientScope::canAccessVisit($user, $visit);
        }

        return false;
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
        if ($user->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            return true;
        }

        if ($user->hasRole(UserRole::Specialist)) {
            return SpecialistPatientScope::canAccessVisit($user, $visit);
        }

        return false;
    }
}
