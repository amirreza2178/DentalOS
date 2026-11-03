<?php

namespace App\Actions\Scheduling;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\AppointmentStatus;
use App\Enums\OperationalEventType;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RescheduleAppointment
{
    public function __construct(
        private ReserveAppointment $reserve,
        private RecordOperationalEvent $events,
    ) {}

    public function reschedule(Appointment $appointment, ReservationData $data, ?User $actor = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $actor): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if (! $appointment->status->canTransitionTo(AppointmentStatus::Rescheduled)) {
                throw new InvalidAppointmentTransitionException($appointment->status, AppointmentStatus::Rescheduled);
            }

            $from = $appointment->status->value;
            $appointment->status = AppointmentStatus::Rescheduled;
            $appointment->save();

            $created = $this->reserve->reserve($data->withLink($appointment->id));

            $this->events->record(
                OperationalEventType::AppointmentRescheduled,
                $appointment,
                $actor,
                $from,
                AppointmentStatus::Rescheduled->value,
            );

            return $created;
        });
    }
}
