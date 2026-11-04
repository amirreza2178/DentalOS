<?php

namespace App\Actions\Scheduling;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\AppointmentStatus;
use App\Enums\CancellationReason;
use App\Enums\OperationalEventType;
use App\Enums\UserRole;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use App\Models\PatientVisit;
use App\Models\User;
use App\Support\ReceptionCancellationGate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelAppointment
{
    public function __construct(
        private RecordOperationalEvent $events,
        private ReceptionCancellationGate $gate,
    ) {}

    public function cancel(
        Appointment $appointment,
        ?User $actor = null,
        ?CancellationReason $reason = null,
        ?string $note = null,
    ): CancelAppointmentResult {
        if ($actor === null || ! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند نوبت را لغو کند.');
        }

        if ($reason === null) {
            throw new InvalidArgumentException('برای لغو نوبت، دلیل ساختاریافته الزامی است.');
        }

        // Bounded retry is safe: deadlock rolls back; committed cancel → already_cancelled.
        return DB::transaction(function () use ($appointment, $actor, $reason, $note): CancelAppointmentResult {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($appointment->status === AppointmentStatus::Cancelled) {
                return new CancelAppointmentResult($appointment, CancelAppointmentResult::OUTCOME_ALREADY_CANCELLED);
            }

            $visit = PatientVisit::query()->where('appointment_id', $appointment->id)->lockForUpdate()->first();
            if ($visit !== null) {
                $appointment->setRelation('visit', $visit);
            }

            $block = $this->gate->blockReason($appointment);
            if ($block !== null) {
                throw new ReceptionConflictException($block);
            }

            if (! $appointment->status->canTransitionTo(AppointmentStatus::Cancelled)) {
                throw new InvalidAppointmentTransitionException($appointment->status, AppointmentStatus::Cancelled);
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

            return new CancelAppointmentResult($appointment, CancelAppointmentResult::OUTCOME_CANCELLED);
        }, 3);
    }
}
