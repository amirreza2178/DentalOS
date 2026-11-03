<?php

namespace App\Actions\Reception;

use App\Enums\AppointmentStatus;
use App\Enums\OperationalEventType;
use App\Enums\QueueStatus;
use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Exceptions\Reception\InvalidVisitTransitionException;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Models\Appointment;
use App\Models\PatientVisit;
use App\Models\User;
use App\Models\WaitingQueueEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CompleteVisit
{
    public function __construct(private readonly RecordOperationalEvent $events) {}

    public function handle(PatientVisit $visit, User $actor): PatientVisit
    {
        if (! $actor->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist)) {
            throw new AuthorizationException('اتمام ویزیت برای نقش شما مجاز نیست.');
        }

        return DB::transaction(function () use ($visit, $actor): PatientVisit {
            $visit = PatientVisit::query()->whereKey($visit->id)->lockForUpdate()->firstOrFail();

            if (! $visit->status->canTransitionTo(VisitStatus::Completed)) {
                throw new InvalidVisitTransitionException($visit->status, VisitStatus::Completed);
            }

            $queue = WaitingQueueEntry::query()
                ->where('patient_visit_id', $visit->id)
                ->whereIn('status', QueueStatus::activeValues())
                ->lockForUpdate()
                ->first();

            if ($queue === null || $queue->status !== QueueStatus::InService) {
                throw new ReceptionConflictException('اتمام ویزیت فقط وقتی مجاز است که بیمار در خدمت باشد.');
            }

            $appointment = null;
            if ($visit->appointment_id !== null) {
                $appointment = Appointment::query()->whereKey($visit->appointment_id)->lockForUpdate()->firstOrFail();
                if (! $appointment->status->canTransitionTo(AppointmentStatus::Completed)) {
                    throw new ReceptionConflictException('وضعیت نوبت برای اتمام ویزیت مجاز نیست.');
                }
            }

            $now = now();
            $fromVisit = $visit->status->value;
            $fromQueue = $queue->status->value;

            $visit->status = VisitStatus::Completed;
            $visit->completed_at = $now;
            $visit->save();

            $queue->status = QueueStatus::Completed;
            $queue->completed_at = $now;
            $queue->save();

            if ($appointment !== null) {
                $fromAppointment = $appointment->status->value;
                $appointment->status = AppointmentStatus::Completed;
                $appointment->save();
                $this->events->record(OperationalEventType::VisitCompleted, $appointment, $actor, $fromAppointment, AppointmentStatus::Completed->value);
            }

            $this->events->record(OperationalEventType::VisitCompleted, $visit, $actor, $fromVisit, VisitStatus::Completed->value);
            $this->events->record(OperationalEventType::QueueCompleted, $queue, $actor, $fromQueue, QueueStatus::Completed->value);

            return $visit->load(['patient', 'appointment.treatmentService', 'latestQueueEntry']);
        });
    }
}
