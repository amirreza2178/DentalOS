<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class CancelAppointment
{
    public function cancel(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if (! $appointment->status->canTransitionTo(AppointmentStatus::Cancelled)) {
                throw new InvalidAppointmentTransitionException($appointment->status, AppointmentStatus::Cancelled);
            }

            $appointment->status = AppointmentStatus::Cancelled;
            $appointment->save();

            return $appointment;
        });
    }
}
