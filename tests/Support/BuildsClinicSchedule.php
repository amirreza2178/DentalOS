<?php

namespace Tests\Support;

use App\Actions\Scheduling\ReservationData;
use App\Actions\Scheduling\ReserveAppointment;
use App\Enums\AppointmentSource;
use App\Enums\Qualification;
use App\Enums\StaffType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Patient;
use App\Models\ShiftBreak;
use App\Models\StaffProfile;
use App\Models\StaffSchedule;
use App\Models\TreatmentService;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Support\Carbon;

trait BuildsClinicSchedule
{
    /**
     * @return array<string, mixed>
     */
    protected function makeClinic(): array
    {
        $day = Carbon::parse('2026-11-07 00:00:00', 'Asia/Tehran');

        $shift = WorkShift::query()->create([
            'name' => 'نوبت صبح',
            'weekday' => $day->dayOfWeek,
            'starts_at' => '08:00:00',
            'ends_at' => '12:00:00',
            'is_active' => true,
        ]);

        $break = ShiftBreak::query()->create([
            'work_shift_id' => $shift->id,
            'name' => 'استراحت',
            'starts_at' => '10:00:00',
            'ends_at' => '10:20:00',
        ]);

        $specialist = $this->clinicStaff(UserRole::Specialist, Qualification::OrthodonticSpecialist, 'دکتر آزمایش');
        $specialistTwo = $this->clinicStaff(UserRole::Specialist, Qualification::OrthodonticSpecialist, 'دکتر دوم');
        $assistant = $this->clinicStaff(UserRole::ClinicalStaff, Qualification::Student, 'کادر آزمایش');
        $assistantTwo = $this->clinicStaff(UserRole::ClinicalStaff, Qualification::QualifiedClinicalPersonnel, 'کادر دوم');

        foreach ([$specialist, $specialistTwo, $assistant, $assistantTwo] as $user) {
            StaffSchedule::query()->create([
                'user_id' => $user->id,
                'work_shift_id' => $shift->id,
                'is_unavailable' => false,
                'is_active' => true,
            ]);
        }

        $chair = Chair::query()->create([
            'code' => 'U1',
            'name' => 'یونیت آزمایش',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $orthoChair = Chair::query()->create([
            'code' => 'U2',
            'name' => 'یونیت ارتودنسی',
            'capabilities' => ['ortho'],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $service = TreatmentService::query()->create([
            'code' => 'ADJ',
            'name' => 'تنظیم',
            'default_duration_minutes' => 40,
            'buffer_minutes' => 5,
            'requires_specialist' => true,
            'required_clinical_staff_count' => 1,
            'requires_chair' => true,
            'is_active' => true,
            'is_bookable_online' => true,
        ]);

        $patient = $this->clinicPatient('سارا', 'آزمایش', 'P-100001');
        $otherPatient = $this->clinicPatient('نیما', 'آزمایش', 'P-100002');

        return compact(
            'day',
            'shift',
            'break',
            'specialist',
            'specialistTwo',
            'assistant',
            'assistantTwo',
            'chair',
            'orthoChair',
            'service',
            'patient',
            'otherPatient',
        );
    }

    protected function clinicStaff(UserRole $role, Qualification $qualification, string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => $role,
            'is_active' => true,
        ]);

        StaffProfile::query()->create([
            'user_id' => $user->id,
            'staff_type' => $role === UserRole::Specialist ? StaffType::Specialist : StaffType::ClinicalStaff,
            'qualification' => $qualification,
            'is_active' => true,
            'can_take_clinical_notes' => $role === UserRole::Specialist,
        ]);

        return $user;
    }

    protected function clinicPatient(string $first, string $last, string $code): Patient
    {
        return Patient::query()->create([
            'code' => $code,
            'first_name' => $first,
            'last_name' => $last,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $clinic
     * @param  array<string, mixed>  $overrides
     */
    protected function reserveAt(array $clinic, string $time, array $overrides = []): Appointment
    {
        $patient = $overrides['patient'] ?? $clinic['patient'];
        $source = $overrides['source'] ?? AppointmentSource::Receptionist;
        $actor = $overrides['actor'] ?? null;
        $actorId = $actor?->id;

        if ($actorId === null && $source === AppointmentSource::Receptionist) {
            $actorId = User::factory()->create([
                'role' => UserRole::Receptionist,
                'is_active' => true,
            ])->id;
        }

        return app(ReserveAppointment::class)->reserve(new ReservationData(
            patientId: $patient->id,
            treatmentServiceId: $clinic['service']->id,
            startsAt: Carbon::parse('2026-11-07 '.$time.':00', 'Asia/Tehran'),
            specialistId: ($overrides['specialist'] ?? $clinic['specialist'])->id,
            clinicalStaffIds: $overrides['staff'] ?? [$clinic['assistant']->id],
            chairId: ($overrides['chair'] ?? $clinic['chair'])->id,
            source: $source,
            actorUserId: $actorId,
            followUpId: isset($overrides['followUp']) ? $overrides['followUp']->id : null,
            followUpAuthorization: $overrides['authorization'] ?? null,
        ));
    }
}
