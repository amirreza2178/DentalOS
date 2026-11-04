<?php

namespace App\Actions\Scheduling;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\AppointmentStatus;
use App\Enums\CancellationReason;
use App\Enums\OperationalEventType;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use App\Models\User;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

class CancelAppointment
{
    public function __construct(private RecordOperationalEvent $events) {}

    public function cancel(
        Appointment $appointment,
        ?User $actor = null,
        ?CancellationReason $reason = null,
        ?string $note = null,
    ): Appointment {
        return DB::transaction(function () use ($appointment, $actor, $reason, $note): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($appointment->status === AppointmentStatus::Cancelled) {
                return $appointment;
            }

            if (! $appointment->status->canTransitionTo(AppointmentStatus::Cancelled)) {
                throw new InvalidAppointmentTransitionException($appointment->status, AppointmentStatus::Cancelled);
            }

            if ($reason === null) {
                throw new InvalidArgumentException('برای لغو نوبت، دلیل ساختاریافته الزامی است.');
            }

            $from = $appointment->status->value;
            $appointment->status = AppointmentStatus::Cancelled;
            $appointment->decision_reason = $reason->encode($note);
            $appointment->decided_at = now(config('app.timezone'));
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
