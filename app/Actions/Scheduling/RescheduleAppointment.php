<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class RescheduleAppointment
{
    public function __construct(private ReserveAppointment $reserve) {}

    public function reschedule(Appointment $appointment, ReservationData $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if (! $appointment->status->canTransitionTo(AppointmentStatus::Rescheduled)) {
                throw new InvalidAppointmentTransitionException($appointment->status, AppointmentStatus::Rescheduled);
            }

            $appointment->status = AppointmentStatus::Rescheduled;
            $appointment->save();

            return $this->reserve->reserve($data->withLink($appointment->id));
        });
    }
}
