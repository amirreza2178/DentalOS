<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, Patient $patient): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === UserRole::Patient) {
            return $patient->user_id !== null && $patient->user_id === $user->id;
        }

        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function archive(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function viewAdminNotes(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function viewClinicalNotes(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Specialist);
    }
}
