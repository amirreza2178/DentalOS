<?php

namespace App\Actions\Reception;

use App\Actions\Scheduling\ReservationMutex;
use App\Enums\AppointmentStatus;
use App\Enums\OperationalEventType;
use App\Enums\QueueStatus;
use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use App\Models\PatientVisit;
use App\Models\User;
use App\Models\WaitingQueueEntry;
use App\Support\ClinicDay;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CheckInAppointment
{
    public function __construct(
        private readonly RecordOperationalEvent $events,
        private readonly ClinicDay $clinicDay,
        private readonly ReservationMutex $mutex,
    ) {}

    /**
     * @return array{visit: PatientVisit, queue: WaitingQueueEntry, created: bool}
     */
    public function handle(Appointment $appointment, User $actor): array
    {
        if (! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند چک‌این کند.');
        }

        return DB::transaction(function () use ($appointment, $actor): array {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $queueDate = $appointment->starts_at->timezone($this->clinicDay->timezone())->toDateString();
            $this->mutex->acquireQueueDay($queueDate);

            $existing = PatientVisit::query()
                ->where('appointment_id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $queue = WaitingQueueEntry::query()
                    ->where('patient_visit_id', $existing->id)
                    ->whereIn('status', QueueStatus::activeValues())
                    ->lockForUpdate()
                    ->first();

                if ($queue === null) {
                    throw new ReceptionConflictException('برای این نوبت ویزیت فعال در صف وجود ندارد. وضعیت را از جزئیات ویزیت بررسی کنید.');
                }

                return [
                    'visit' => $existing->load(['patient', 'appointment.treatmentService', 'latestQueueEntry']),
                    'queue' => $queue,
                    'created' => false,
                ];
            }

            if ($appointment->status === AppointmentStatus::Cancelled
                || $appointment->status === AppointmentStatus::Rescheduled
                || $appointment->status === AppointmentStatus::NoShow
                || $appointment->status === AppointmentStatus::Completed
                || $appointment->status === AppointmentStatus::Draft) {
                throw new ReceptionConflictException('این نوبت برای چک‌این معتبر نیست.');
            }

            if ($appointment->status === AppointmentStatus::PendingApproval) {
                throw new ReceptionConflictException('نوبت هنوز قطعی نشده است. اول آن را تأیید کنید.');
            }

            if ($appointment->status !== AppointmentStatus::Confirmed) {
                throw new ReceptionConflictException('این نوبت در وضعیت فعلی قابل چک‌این نیست.');
            }

            if (! $appointment->status->canTransitionTo(AppointmentStatus::CheckedIn)) {
                throw new InvalidAppointmentTransitionException($appointment->status, AppointmentStatus::CheckedIn);
            }

            $now = now($this->clinicDay->timezone());

            WaitingQueueEntry::query()
                ->whereDate('queue_date', $queueDate)
                ->orderBy('position')
                ->lockForUpdate()
                ->get();

            $nextPosition = (int) WaitingQueueEntry::query()
                ->whereDate('queue_date', $queueDate)
                ->max('position') + 1;

            $visit = PatientVisit::query()->create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'status' => VisitStatus::Waiting,
                'checked_in_by_user_id' => $actor->id,
                'arrived_at' => $now,
            ]);

            $queue = WaitingQueueEntry::query()->create([
                'patient_visit_id' => $visit->id,
                'queue_date' => $queueDate,
                'position' => $nextPosition,
                'status' => QueueStatus::Waiting,
                'arrived_at' => $now,
            ]);

            $from = $appointment->status->value;
            $appointment->status = AppointmentStatus::CheckedIn;
            $appointment->save();

            $this->events->record(OperationalEventType::PatientCheckedIn, $appointment, $actor, $from, AppointmentStatus::CheckedIn->value);
            $this->events->record(OperationalEventType::VisitCreated, $visit, $actor, null, VisitStatus::Waiting->value);
            $this->events->record(OperationalEventType::QueueEntryCreated, $queue, $actor, null, QueueStatus::Waiting->value);

            return [
                'visit' => $visit->load(['patient', 'appointment.treatmentService', 'latestQueueEntry']),
                'queue' => $queue,
                'created' => true,
            ];
        }, 3);
    }
}
