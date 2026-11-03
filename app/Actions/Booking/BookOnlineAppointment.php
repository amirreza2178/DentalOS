<?php

namespace App\Actions\Booking;

use App\Actions\Patients\SavePatient;
use App\Actions\Scheduling\AvailabilityService;
use App\Actions\Scheduling\ReservationData;
use App\Actions\Scheduling\ReserveAppointment;
use App\Actions\Scheduling\SlotOption;
use App\Enums\AppointmentSource;
use App\Enums\PatientIdentityStatus;
use App\Enums\PatientStatus;
use App\Exceptions\Scheduling\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\TreatmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookOnlineAppointment
{
    public function __construct(
        private AvailabilityService $availability,
        private ReserveAppointment $reserve,
        private SavePatient $patients,
    ) {}

    /**
     * @param  array{first_name: string, last_name: string, mobile: string, treatment_service_id: int, starts_at: Carbon, patient_notes?: ?string, idempotency_key: string}  $input
     */
    public function book(array $input): Appointment
    {
        $cacheKey = 'online-booking:idempotency:'.$input['idempotency_key'];

        $existingId = Cache::get($cacheKey);
        if (is_int($existingId) || (is_string($existingId) && ctype_digit($existingId))) {
            $existing = Appointment::query()->find((int) $existingId);
            if ($existing !== null) {
                return $existing->load(['patient', 'treatmentService', 'allocations']);
            }
        }

        $appointment = DB::transaction(function () use ($input): Appointment {
            $service = TreatmentService::query()
                ->whereKey($input['treatment_service_id'])
                ->lockForUpdate()
                ->first();

            if ($service === null || ! $service->is_active || ! $service->is_bookable_online) {
                throw new SlotUnavailableException(['این خدمت برای رزرو آنلاین در دسترس نیست.']);
            }

            $startsAt = $input['starts_at']->copy()->timezone(config('app.timezone'))->seconds(0);
            $opening = $this->availability->opening($service, $startsAt);

            if ($opening === null) {
                throw new SlotUnavailableException(['این زمان دیگر آزاد نیست. لطفاً زمان دیگری انتخاب کنید.']);
            }

            $resources = $this->pickResources($service, $opening);

            $patient = $this->patients->create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'mobile' => $input['mobile'],
                'status' => PatientStatus::Active,
                'identity_status' => PatientIdentityStatus::UnverifiedOnline,
                'notes_admin' => 'ایجاد از رزرو آنلاین عمومی؛ بدون اتصال خودکار به پرونده موجود.',
            ]);

            $appointment = $this->reserve->reserve(new ReservationData(
                patientId: $patient->id,
                treatmentServiceId: $service->id,
                startsAt: $startsAt,
                specialistId: $resources['specialist_id'],
                clinicalStaffIds: $resources['clinical_staff_ids'],
                chairId: $resources['chair_id'],
                source: AppointmentSource::Online,
                actorUserId: null,
                patientNotes: $input['patient_notes'] ?? null,
            ));

            if ($appointment->public_reference === null) {
                $appointment->public_reference = (string) Str::uuid();
                $appointment->save();
            }

            return $appointment->load(['patient', 'treatmentService', 'allocations']);
        }, 3);

        Cache::put($cacheKey, $appointment->id, now()->addMinutes(30));

        return $appointment;
    }

    /**
     * @return array{specialist_id: ?int, clinical_staff_ids: list<int>, chair_id: ?int}
     */
    private function pickResources(TreatmentService $service, SlotOption $opening): array
    {
        $specialistId = null;
        if ($service->requires_specialist) {
            if ($opening->specialistIds === []) {
                throw new SlotUnavailableException(['در این زمان متخصص آزاد نیست.']);
            }
            $specialistId = $opening->specialistIds[0];
        }

        $staffIds = [];
        if ($service->required_clinical_staff_count > 0) {
            if (count($opening->clinicalStaffIds) < $service->required_clinical_staff_count) {
                throw new SlotUnavailableException(['در این زمان کادر بالینی کافی آزاد نیست.']);
            }
            $staffIds = array_slice($opening->clinicalStaffIds, 0, $service->required_clinical_staff_count);
        }

        $chairId = null;
        if ($service->requires_chair) {
            if ($opening->chairIds === []) {
                throw new SlotUnavailableException(['در این زمان یونیت آزاد نیست.']);
            }
            $chairId = $opening->chairIds[0];
        }

        return [
            'specialist_id' => $specialistId,
            'clinical_staff_ids' => $staffIds,
            'chair_id' => $chairId,
        ];
    }
}
