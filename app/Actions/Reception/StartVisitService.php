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

class StartVisitService
{
    public function __construct(private readonly RecordOperationalEvent $events) {}

    public function handle(PatientVisit $visit, User $actor): PatientVisit
    {
        if (! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند ویزیت را شروع کند.');
        }

        return DB::transaction(function () use ($visit, $actor): PatientVisit {
            $visit = PatientVisit::query()->whereKey($visit->id)->lockForUpdate()->firstOrFail();

            if (! $visit->status->canTransitionTo(VisitStatus::InProgress)) {
                throw new InvalidVisitTransitionException($visit->status, VisitStatus::InProgress);
            }

            $queue = WaitingQueueEntry::query()
                ->where('patient_visit_id', $visit->id)
                ->whereIn('status', QueueStatus::activeValues())
                ->lockForUpdate()
                ->first();

            if ($queue === null) {
                throw new ReceptionConflictException('ورودی فعال صف برای این ویزیت پیدا نشد.');
            }

            if (! $queue->status->canTransitionTo(QueueStatus::InService)) {
                throw new ReceptionConflictException('وضعیت صف برای شروع خدمت مجاز نیست.');
            }

            $appointment = null;
            if ($visit->appointment_id !== null) {
                $appointment = Appointment::query()->whereKey($visit->appointment_id)->lockForUpdate()->firstOrFail();
                if (! $appointment->status->canTransitionTo(AppointmentStatus::InProgress)) {
                    throw new ReceptionConflictException('وضعیت نوبت برای شروع ویزیت مجاز نیست.');
                }
            }

            $now = now();
            $fromVisit = $visit->status->value;
            $fromQueue = $queue->status->value;

            $visit->status = VisitStatus::InProgress;
            $visit->started_at = $now;
            $visit->save();

            $queue->status = QueueStatus::InService;
            $queue->started_at = $now;
            $queue->save();

            if ($appointment !== null) {
                $fromAppointment = $appointment->status->value;
                $appointment->status = AppointmentStatus::InProgress;
                $appointment->save();
                $this->events->record(OperationalEventType::VisitStarted, $appointment, $actor, $fromAppointment, AppointmentStatus::InProgress->value);
            }

            $this->events->record(OperationalEventType::VisitStarted, $visit, $actor, $fromVisit, VisitStatus::InProgress->value);
            $this->events->record(OperationalEventType::QueueServiceStarted, $queue, $actor, $fromQueue, QueueStatus::InService->value);

            return $visit->load(['patient', 'appointment.treatmentService', 'latestQueueEntry']);
        });
    }
}
