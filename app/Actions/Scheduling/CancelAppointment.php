<?php

namespace App\Actions\Scheduling;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\AppointmentStatus;
use App\Enums\OperationalEventType;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelAppointment
{
    public function __construct(private RecordOperationalEvent $events) {}

    public function cancel(Appointment $appointment, ?User $actor = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($appointment->status === AppointmentStatus::Cancelled) {
                return $appointment;
            }

            if (! $appointment->status->canTransitionTo(AppointmentStatus::Cancelled)) {
                throw new InvalidAppointmentTransitionException($appointment->status, AppointmentStatus::Cancelled);
            }

            $from = $appointment->status->value;
            $appointment->status = AppointmentStatus::Cancelled;
            $appointment->save();

            $this->events->record(
                OperationalEventType::AppointmentCancelled,
                $appointment,
                $actor,
                $from,
                AppointmentStatus::Cancelled->value,
            );

            return $appointment;
        });
    }
}
