<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Support\SpecialistPatientScope;

class TreatmentPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Specialist, UserRole::Receptionist);
    }

    public function view(User $user, TreatmentPlan $plan): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Specialist)) {
            return SpecialistPatientScope::canAccessPatient($user, $plan->patient)
                || $plan->created_by_user_id === $user->id;
        }

        if ($user->hasRole(UserRole::Receptionist)) {
            return true;
        }

        return false;
    }

    public function viewClinicalSummary(User $user, TreatmentPlan $plan): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Specialist)
            && $this->view($user, $plan);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Specialist);
    }

    public function update(User $user, TreatmentPlan $plan): bool
    {
        if (! $plan->status->canBeEdited()) {
            return false;
        }

        return $this->create($user) && $this->view($user, $plan);
    }
}
