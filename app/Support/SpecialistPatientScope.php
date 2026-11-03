<?php

namespace App\Support;

use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class SpecialistPatientScope
{
    public static function isSpecialistOnly(User $user): bool
    {
        return $user->hasRole(UserRole::Specialist)
            && ! $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public static function canAccessPatient(User $user, Patient $patient): bool
    {
        if (! self::isSpecialistOnly($user)) {
            return true;
        }

        return Appointment::query()
            ->where('patient_id', $patient->id)
            ->whereHas(
                'allocations',
                fn (Builder $allocation) => $allocation
                    ->where('resource_type', ResourceType::Specialist->value)
                    ->where('resource_id', $user->id),
            )
            ->exists();
    }

    public static function canAccessVisit(User $user, PatientVisit $visit): bool
    {
        if (! self::isSpecialistOnly($user)) {
            return true;
        }

        $appointment = $visit->appointment;

        if ($appointment === null) {
            $appointment = $visit->appointment()->first();
        }

        if ($appointment === null) {
            return false;
        }

        return $appointment->allocations()
            ->where('resource_type', ResourceType::Specialist->value)
            ->where('resource_id', $user->id)
            ->exists();
    }

    /**
     * @param  Builder<Patient>  $query
     * @return Builder<Patient>
     */
    public static function constrainPatients(Builder $query, User $user): Builder
    {
        if (! self::isSpecialistOnly($user)) {
            return $query;
        }

        return $query->whereHas(
            'appointments.allocations',
            fn (Builder $allocation) => $allocation
                ->where('resource_type', ResourceType::Specialist->value)
                ->where('resource_id', $user->id),
        );
    }
}
