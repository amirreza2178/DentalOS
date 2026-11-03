<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\BookingConfirmationMode;
use App\Enums\FollowUpAuthorization;
use App\Enums\FollowUpStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Exceptions\Scheduling\FollowUpConfirmationRequiredException;
use App\Exceptions\Scheduling\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\FollowUp;
use App\Models\TreatmentService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReserveAppointment
{
    public function __construct(
        private AvailabilityService $availability,
        private ReservationMutex $mutex,
    ) {}

    public function reserve(ReservationData $data): Appointment
    {
        $attempts = 0;

        while (true) {
            try {
                return $this->attempt($data);
            } catch (QueryException $exception) {
                if (! $this->isDatabaseBusy($exception) || $attempts >= 4) {
                    if ($this->isDatabaseBusy($exception)) {
                        throw new SlotUnavailableException(['این بازه همین الان رزرو شد.']);
                    }

                    throw $exception;
                }

                $attempts++;
                usleep(200000);
            }
        }
    }

    private function attempt(ReservationData $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA busy_timeout = 5000');
            }

            $service = TreatmentService::query()->whereKey($data->treatmentServiceId)->lockForUpdate()->first();
            $followUp = $this->lockedFollowUp($data);
            $status = $this->resolveStatus($data, $service, $followUp);

            $resources = $this->resources($data);
            $this->mutex->acquire($resources);

            $reasons = $this->availability->reasons($data);

            if ($reasons !== []) {
                throw new SlotUnavailableException($reasons);
            }

            [$start, $serviceEnd, $allocationEnd] = $this->availability->allocationWindow($service, $data->startsAt);

            if ($followUp !== null) {
                $day = $start->toDateString();
                $windowStart = $followUp->proposed_window_start->toDateString();
                $windowEnd = $followUp->proposed_window_end->toDateString();

                if ($day < $windowStart || $day > $windowEnd) {
                    throw new SlotUnavailableException(['تاریخ نوبت خارج از بازه پیشنهادی پیگیری است.']);
                }
            }

            $mode = $service === null ? null : $this->configuredMode($service);
            $actor = $data->actorUserId === null ? null : User::query()->find($data->actorUserId);

            $appointment = Appointment::query()->create([
                'patient_id' => $data->patientId,
                'treatment_service_id' => $data->treatmentServiceId,
                'starts_at' => $start,
                'ends_at' => $serviceEnd,
                'status' => $status,
                'source' => $data->source,
                'confirmation_mode' => $mode,
                'booked_by_user_id' => $actor?->id,
                'approved_by_user_id' => $status === AppointmentStatus::Confirmed ? $actor?->id : null,
                'rescheduled_from_id' => $data->rescheduledFromId,
                'follow_up_id' => $followUp?->id,
                'patient_notes' => $data->patientNotes,
                'public_reference' => $data->source === AppointmentSource::Online ? (string) Str::uuid() : null,
            ]);

            foreach ($this->allocationPairs($data) as $pair) {
                $appointment->allocations()->create([
                    'resource_type' => $pair['type'],
                    'resource_id' => $pair['id'],
                    'starts_at' => $start,
                    'ends_at' => $allocationEnd,
                    'is_required' => true,
                ]);
            }

            if ($followUp !== null) {
                $followUp->status = FollowUpStatus::Scheduled;
                $followUp->converted_appointment_id = $appointment->id;

                if ($data->followUpAuthorization === FollowUpAuthorization::Patient) {
                    $followUp->patient_confirmed_at = now();
                }

                if ($data->followUpAuthorization === FollowUpAuthorization::Receptionist) {
                    $followUp->authorized_by_user_id = $actor?->id;
                }

                $followUp->save();
            }

            return $appointment->load('allocations');
        }, 3);
    }

    private function isDatabaseBusy(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'database is locked')
            || str_contains($message, 'database is busy')
            || str_contains($message, 'deadlock');
    }

    private function lockedFollowUp(ReservationData $data): ?FollowUp
    {
        if ($data->followUpId === null) {
            return null;
        }

        $followUp = FollowUp::query()->whereKey($data->followUpId)->lockForUpdate()->first();

        if ($followUp === null) {
            throw new FollowUpConfirmationRequiredException('پیگیری انتخاب‌شده پیدا نشد.');
        }

        if ($followUp->patient_id !== $data->patientId) {
            throw new FollowUpConfirmationRequiredException('پیگیری به بیمار دیگری تعلق دارد.');
        }

        if (! $followUp->status->canBeScheduled() || $followUp->converted_appointment_id !== null) {
            throw new FollowUpConfirmationRequiredException('این پیگیری در وضعیتی نیست که بتوان برای آن نوبت ساخت.');
        }

        return $followUp;
    }

    private function resolveStatus(ReservationData $data, ?TreatmentService $service, ?FollowUp $followUp): AppointmentStatus
    {
        if ($service === null) {
            throw new SlotUnavailableException(['خدمت انتخاب‌شده فعال نیست.']);
        }

        $mode = $this->configuredMode($service);
        $authorized = $this->authorizedActor($data);

        if ($data->source === AppointmentSource::Online) {
            if ($followUp !== null && $data->followUpAuthorization !== FollowUpAuthorization::Patient) {
                throw new FollowUpConfirmationRequiredException();
            }

            if ($mode === BookingConfirmationMode::Immediate->value) {
                return AppointmentStatus::Confirmed;
            }

            return AppointmentStatus::PendingApproval;
        }

        if ($data->source === AppointmentSource::SystemSuggested) {
            if ($data->followUpAuthorization === FollowUpAuthorization::Receptionist && $authorized) {
                return AppointmentStatus::Confirmed;
            }

            if ($data->followUpAuthorization === FollowUpAuthorization::Patient && $mode === BookingConfirmationMode::Immediate->value) {
                return AppointmentStatus::Confirmed;
            }

            if ($data->followUpAuthorization === FollowUpAuthorization::Patient) {
                return AppointmentStatus::PendingApproval;
            }

            throw new FollowUpConfirmationRequiredException();
        }

        if (! $authorized) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند نوبت داخلی ثبت کند.');
        }

        if ($followUp !== null && ! in_array($data->followUpAuthorization, [FollowUpAuthorization::Patient, FollowUpAuthorization::Receptionist], true)) {
            throw new FollowUpConfirmationRequiredException();
        }

        return AppointmentStatus::Confirmed;
    }

    private function authorizedActor(ReservationData $data): bool
    {
        if ($data->actorUserId === null) {
            return false;
        }

        $actor = User::query()->find($data->actorUserId);

        return $actor !== null && $actor->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    private function configuredMode(TreatmentService $service): ?string
    {
        if ($service->booking_confirmation_mode !== null) {
            return $service->booking_confirmation_mode->value;
        }

        return ClinicSetting::getValue(ClinicSetting::BOOKING_CONFIRMATION_MODE);
    }

    /**
     * @return list<array{type: string, id: int}>
     */
    private function resources(ReservationData $data): array
    {
        $resources = $this->allocationPairs($data);
        $resources[] = ['type' => ReservationMutex::PATIENT, 'id' => $data->patientId];

        return $resources;
    }

    /**
     * @return list<array{type: string, id: int}>
     */
    private function allocationPairs(ReservationData $data): array
    {
        $resources = [];

        if ($data->chairId !== null) {
            $resources[] = ['type' => ResourceType::Chair->value, 'id' => $data->chairId];
        }

        if ($data->specialistId !== null) {
            $resources[] = ['type' => ResourceType::Specialist->value, 'id' => $data->specialistId];
        }

        foreach ($data->clinicalStaffIds as $staffId) {
            $resources[] = ['type' => ResourceType::ClinicalStaff->value, 'id' => $staffId];
        }

        return $resources;
    }
}
