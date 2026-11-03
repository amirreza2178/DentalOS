<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FollowUp;
use App\Models\User;
use App\Support\SpecialistPatientScope;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            return true;
        }

        if ($user->hasRole(UserRole::Specialist)) {
            return SpecialistPatientScope::canAccessPatient($user, $followUp->patient)
                || $followUp->created_by === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Specialist);
    }

    public function updateOperational(User $user, FollowUp $followUp): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function schedule(User $user, FollowUp $followUp): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist)
            && $followUp->status->canBeScheduled();
    }
}
