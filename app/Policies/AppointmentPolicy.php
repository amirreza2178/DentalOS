<?php

namespace App\Policies;

use App\Enums\AppointmentSource;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            return true;
        }

        if ($user->hasRole(UserRole::Specialist)) {
            return $this->specialistOwns($user, $appointment);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->create($user);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->create($user);
    }

    public function checkIn(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function viewOnlineRequests(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function viewOnlineRequest(User $user, Appointment $appointment): bool
    {
        return $appointment->source === AppointmentSource::Online
            && $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function decideOnline(User $user, Appointment $appointment): bool
    {
        return $this->viewOnlineRequest($user, $appointment);
    }

    private function specialistOwns(User $user, Appointment $appointment): bool
    {
        return $appointment->allocations()
            ->where('resource_type', ResourceType::Specialist->value)
            ->where('resource_id', $user->id)
            ->exists();
    }
}
