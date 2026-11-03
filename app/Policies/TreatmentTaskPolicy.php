<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TreatmentTask;
use App\Models\User;
use App\Support\SpecialistPatientScope;

class TreatmentTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(
            UserRole::Admin,
            UserRole::Specialist,
            UserRole::ClinicalStaff,
            UserRole::Receptionist,
        );
    }

    public function view(User $user, TreatmentTask $task): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            return true;
        }

        if ($user->hasRole(UserRole::Specialist)) {
            $plan = $task->plan;

            return SpecialistPatientScope::canAccessPatient($user, $plan->patient)
                || $plan->created_by_user_id === $user->id;
        }

        if ($user->hasRole(UserRole::ClinicalStaff)) {
            return $task->assignee_user_id !== null && $task->assignee_user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Specialist);
    }

    public function assign(User $user, TreatmentTask $task): bool
    {
        return $this->create($user) && $this->view($user, $task);
    }

    public function updateStatus(User $user, TreatmentTask $task): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Specialist)) {
            return $this->view($user, $task);
        }

        if ($user->hasRole(UserRole::ClinicalStaff)) {
            return $task->assignee_user_id === $user->id;
        }

        return false;
    }

    public function complete(User $user, TreatmentTask $task): bool
    {
        return $this->updateStatus($user, $task);
    }

    public function cancel(User $user, TreatmentTask $task): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Specialist) && $this->view($user, $task);
    }
}
