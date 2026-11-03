<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Enums\PatientStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AppointmentResourceAllocation;
use App\Models\Chair;
use App\Models\Holiday;
use App\Models\Patient;
use App\Models\StaffSchedule;
use App\Models\TreatmentService;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\ClockInput;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon}
     */
    public function allocationWindow(TreatmentService $service, Carbon $startsAt): array
    {
        $starts = $startsAt->copy()->timezone(config('app.timezone'))->seconds(0);
        $serviceEnds = $starts->copy()->addMinutes($service->default_duration_minutes);
        $allocationEnds = $serviceEnds->copy()->addMinutes($service->buffer_minutes);

        return [$starts, $serviceEnds, $allocationEnds];
    }

    public function opening(TreatmentService $service, Carbon $startsAt, ?int $patientId = null): ?SlotOption
    {
        if (! $service->is_active) {
            return null;
        }

        return $this->optionFor($service, $startsAt->copy()->timezone(config('app.timezone'))->seconds(0), $patientId);
    }

    /**
     * @return list<SlotOption>
     */
    public function slots(TreatmentService $service, string $gregorianDate, ?int $patientId = null): array
    {
        if (! $service->is_active) {
            return [];
        }

        $day = Carbon::parse($gregorianDate, config('app.timezone'))->startOfDay();
        $shifts = $this->activeShifts($day);

        if ($shifts->isEmpty() || $this->closedHoliday($day) !== null) {
            return [];
        }

        $slots = [];

        foreach ($shifts as $shift) {
            $cursor = $this->onDate($day, $shift->starts_at);
            $shiftEnd = $this->onDate($day, $shift->ends_at);

            while ($cursor->copy()->addMinutes($service->occupiedMinutes())->lessThanOrEqualTo($shiftEnd)) {
                $allocationEnd = $cursor->copy()->addMinutes($service->occupiedMinutes());

                if (! $this->overlapsBreak($shift, $cursor, $allocationEnd)) {
                    $option = $this->optionFor($service, $cursor->copy(), $patientId);

                    if ($option !== null) {
                        $slots[] = $option;
                    }
                }

                $cursor = $cursor->addMinutes($service->default_duration_minutes);
            }
        }

        return $slots;
    }

    /**
     * @return list<string>
     */
    public function reasons(ReservationData $data): array
    {
        $service = TreatmentService::query()->find($data->treatmentServiceId);

        if ($service === null || ! $service->is_active) {
            return ['خدمت انتخاب‌شده فعال نیست.'];
        }

        $patient = Patient::withTrashed()->find($data->patientId);

        if ($patient === null) {
            return ['بیمار پیدا نشد.'];
        }

        if ($patient->trashed()) {
            return ['پرونده بیمار بایگانی شده و نوبت جدید نمی‌گیرد.'];
        }

        if ($patient->status !== PatientStatus::Active) {
            return ['بیمار غیرفعال است و نوبت جدید نمی‌گیرد.'];
        }

        [$start, $serviceEnd, $allocationEnd] = $this->allocationWindow($service, $data->startsAt);

        if ($start->lessThanOrEqualTo(now(config('app.timezone')))) {
            return ['این زمان گذشته است و قابل رزرو نیست.'];
        }

        if ($start->toDateString() !== $allocationEnd->copy()->subSecond()->toDateString()) {
            return ['بازه نوبت باید داخل همان روز بماند.'];
        }

        $clinic = $this->clinicProblems($start, $allocationEnd);

        if ($clinic !== []) {
            return $clinic;
        }

        return $this->resourceProblems($service, $data, $start, $serviceEnd, $allocationEnd);
    }

    /**
     * @return list<string>
     */
    private function clinicProblems(Carbon $start, Carbon $end): array
    {
        $holiday = $this->closedHoliday($start);

        if ($holiday !== null) {
            return ['این تاریخ تعطیل است ('.$holiday->title.').'];
        }

        $shifts = $this->activeShifts($start);

        if ($shifts->isEmpty()) {
            return ['برای این روز شیفت کاری فعالی تعریف نشده است.'];
        }

        $covers = false;
        $hitsBreak = false;

        foreach ($shifts as $shift) {
            if (! $this->covers($start, $end, $shift->starts_at, $shift->ends_at)) {
                continue;
            }

            if ($this->overlapsBreak($shift, $start, $end)) {
                $hitsBreak = true;

                continue;
            }

            $covers = true;
            break;
        }

        if ($covers) {
            return [];
        }

        return [$hitsBreak
            ? 'این بازه با زمان استراحت شیفت تداخل دارد.'
            : 'این بازه خارج از شیفت کاری است.'];
    }

    /**
     * @return list<string>
     */
    private function resourceProblems(TreatmentService $service, ReservationData $data, Carbon $start, Carbon $serviceEnd, Carbon $allocationEnd): array
    {
        $reasons = [];
        $staffIds = array_values(array_unique($data->clinicalStaffIds));

        if (count($staffIds) !== count($data->clinicalStaffIds)) {
            $reasons[] = 'یک نفر را نمی‌توان چند بار برای همین نوبت انتخاب کرد.';
        }

        if ($data->specialistId !== null && in_array($data->specialistId, $staffIds, true)) {
            $reasons[] = 'متخصص و کادر بالینی این نوبت باید اشخاص متفاوت باشند.';
        }

        if ($service->requires_specialist) {
            $reasons = array_merge($reasons, $this->specialistProblems($data->specialistId, $start, $allocationEnd, $data->ignoreAppointmentId));
        } elseif ($data->specialistId !== null) {
            $reasons[] = 'این خدمت به متخصص نیاز ندارد.';
        }

        if ($service->required_clinical_staff_count > 0) {
            $reasons = array_merge($reasons, $this->staffProblems(
                $service->required_clinical_staff_count,
                $staffIds,
                $start,
                $allocationEnd,
                $data->ignoreAppointmentId,
            ));
        } elseif ($staffIds !== []) {
            $reasons[] = 'این خدمت به کادر بالینی نیاز ندارد.';
        }

        if ($service->requires_chair) {
            $reasons = array_merge($reasons, $this->chairProblems(
                $service,
                $data->chairId,
                $start,
                $allocationEnd,
                $data->ignoreAppointmentId,
            ));
        } elseif ($data->chairId !== null) {
            $reasons[] = 'این خدمت به یونیت نیاز ندارد.';
        }

        if ($this->patientBusy($data->patientId, $start, $serviceEnd, $data->ignoreAppointmentId)) {
            $reasons[] = 'این بیمار در این بازه نوبت دیگری دارد.';
        }

        return array_values(array_unique($reasons));
    }

    /**
     * @return list<string>
     */
    private function specialistProblems(?int $specialistId, Carbon $start, Carbon $end, ?int $ignoreAppointmentId): array
    {
        if ($specialistId === null) {
            return [$this->eligibleSpecialists($start, $end, $ignoreAppointmentId)->isEmpty()
                ? 'متخصص فعال و آزادی در برنامه این ساعت نیست.'
                : 'انتخاب متخصص برای این خدمت لازم است.'];
        }

        $specialist = $this->allocatableUser($specialistId, UserRole::Specialist);

        if ($specialist === null) {
            return ['متخصص انتخاب‌شده فعال نیست یا نقش متخصص ندارد.'];
        }

        if (! $this->staffCovers($specialist, $start, $end)) {
            return ['متخصص انتخاب‌شده در این ساعت برنامه کاری ندارد.'];
        }

        if ($this->resourceBusy(ResourceType::Specialist, $specialist->id, $start, $end, $ignoreAppointmentId)) {
            return ['متخصص انتخاب‌شده در این بازه نوبت دیگری دارد.'];
        }

        return [];
    }

    /**
     * @param  list<int>  $staffIds
     * @return list<string>
     */
    private function staffProblems(int $required, array $staffIds, Carbon $start, Carbon $end, ?int $ignoreAppointmentId): array
    {
        if (count($staffIds) !== $required) {
            $eligible = $this->eligibleClinicalStaff($start, $end, $ignoreAppointmentId);

            if ($eligible->count() < $required) {
                return ['کادر بالینی فعال و آزاد به تعداد نیاز این خدمت در برنامه این ساعت نیست.'];
            }

            return ['این خدمت به '.$required.' کادر بالینی نیاز دارد.'];
        }

        $reasons = [];

        foreach ($staffIds as $staffId) {
            $staff = $this->allocatableUser($staffId, UserRole::ClinicalStaff);

            if ($staff === null) {
                $reasons[] = 'کادر بالینی انتخاب‌شده فعال نیست.';

                continue;
            }

            if (! $this->staffCovers($staff, $start, $end)) {
                $reasons[] = 'کادر بالینی انتخاب‌شده در این ساعت برنامه کاری ندارد.';

                continue;
            }

            if ($this->resourceBusy(ResourceType::ClinicalStaff, $staff->id, $start, $end, $ignoreAppointmentId)) {
                $reasons[] = 'کادر بالینی انتخاب‌شده در این بازه نوبت دیگری دارد.';
            }
        }

        return $reasons;
    }

    /**
     * @return list<string>
     */
    private function chairProblems(TreatmentService $service, ?int $chairId, Carbon $start, Carbon $end, ?int $ignoreAppointmentId): array
    {
        if ($chairId === null) {
            return [$this->eligibleChairs($service, $start, $end, $ignoreAppointmentId)->isEmpty()
                ? 'یونیت فعال و مناسب این خدمت در این بازه آزاد نیست.'
                : 'انتخاب یونیت برای این خدمت لازم است.'];
        }

        $chair = Chair::query()->find($chairId);

        if ($chair === null || ! $chair->is_active) {
            return ['یونیت انتخاب‌شده فعال نیست.'];
        }

        if (! $chair->supportsCapabilities($service->capabilityRequirements())) {
            return ['یونیت انتخاب‌شده قابلیت لازم این خدمت را ندارد.'];
        }

        if ($this->resourceBusy(ResourceType::Chair, $chair->id, $start, $end, $ignoreAppointmentId)) {
            return ['یونیت انتخاب‌شده در این بازه نوبت دیگری دارد.'];
        }

        return [];
    }

    private function optionFor(TreatmentService $service, Carbon $start, ?int $patientId): ?SlotOption
    {
        [, $serviceEnd, $allocationEnd] = $this->allocationWindow($service, $start);

        if ($start->lessThanOrEqualTo(now(config('app.timezone')))) {
            return null;
        }

        if ($this->clinicProblems($start, $allocationEnd) !== []) {
            return null;
        }

        if ($patientId !== null && $this->patientBusy($patientId, $start, $serviceEnd, null)) {
            return null;
        }

        $specialists = $service->requires_specialist
            ? $this->eligibleSpecialists($start, $allocationEnd, null)
            : collect();

        if ($service->requires_specialist && $specialists->isEmpty()) {
            return null;
        }

        $staff = $service->required_clinical_staff_count > 0
            ? $this->eligibleClinicalStaff($start, $allocationEnd, null)
            : collect();

        if ($staff->count() < $service->required_clinical_staff_count) {
            return null;
        }

        $chairs = $service->requires_chair
            ? $this->eligibleChairs($service, $start, $allocationEnd, null)
            : collect();

        if ($service->requires_chair && $chairs->isEmpty()) {
            return null;
        }

        return new SlotOption(
            startsAt: $start,
            endsAt: $serviceEnd,
            specialistIds: $specialists->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            clinicalStaffIds: $staff->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            chairIds: $chairs->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        );
    }

    /**
     * @return Collection<int, User>
     */
    private function eligibleSpecialists(Carbon $start, Carbon $end, ?int $ignoreAppointmentId): Collection
    {
        return $this->roleUsers(UserRole::Specialist)
            ->filter(fn (User $user): bool => $this->staffCovers($user, $start, $end))
            ->filter(fn (User $user): bool => ! $this->resourceBusy(ResourceType::Specialist, $user->id, $start, $end, $ignoreAppointmentId))
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function eligibleClinicalStaff(Carbon $start, Carbon $end, ?int $ignoreAppointmentId): Collection
    {
        return $this->roleUsers(UserRole::ClinicalStaff)
            ->filter(fn (User $user): bool => $this->staffCovers($user, $start, $end))
            ->filter(fn (User $user): bool => ! $this->resourceBusy(ResourceType::ClinicalStaff, $user->id, $start, $end, $ignoreAppointmentId))
            ->values();
    }

    /**
     * @return Collection<int, Chair>
     */
    private function eligibleChairs(TreatmentService $service, Carbon $start, Carbon $end, ?int $ignoreAppointmentId): Collection
    {
        return Chair::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->filter(fn (Chair $chair): bool => $chair->supportsCapabilities($service->capabilityRequirements()))
            ->filter(fn (Chair $chair): bool => ! $this->resourceBusy(ResourceType::Chair, $chair->id, $start, $end, $ignoreAppointmentId))
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function roleUsers(UserRole $role): Collection
    {
        return User::query()
            ->where('role', $role->value)
            ->where('is_active', true)
            ->with('staffProfile')
            ->orderBy('name')
            ->get()
            ->filter(function (User $user): bool {
                return $user->staffProfile !== null && $user->staffProfile->is_active;
            })
            ->values();
    }

    private function allocatableUser(int $userId, UserRole $role): ?User
    {
        $user = User::query()->with('staffProfile')->find($userId);

        if ($user === null || ! $user->is_active || $user->role !== $role) {
            return null;
        }

        if ($user->staffProfile === null || ! $user->staffProfile->is_active) {
            return null;
        }

        return $user;
    }

    private function staffCovers(User $user, Carbon $start, Carbon $end): bool
    {
        $date = $start->toDateString();
        $dated = StaffSchedule::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereDate('schedule_date', $date)
            ->with('workShift')
            ->get();

        if ($dated->contains(fn (StaffSchedule $schedule): bool => $schedule->is_unavailable)) {
            return false;
        }

        if ($dated->isNotEmpty()) {
            return $dated->contains(fn (StaffSchedule $schedule): bool => $this->scheduleCovers($schedule, $start, $end));
        }

        return StaffSchedule::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('schedule_date')
            ->where('is_unavailable', false)
            ->with('workShift')
            ->get()
            ->contains(function (StaffSchedule $schedule) use ($start, $end): bool {
                $shift = $schedule->workShift;

                if ($shift === null || ! $shift->is_active || $shift->weekday->value !== $start->dayOfWeek) {
                    return false;
                }

                return $this->covers(
                    $start,
                    $end,
                    $schedule->starts_at ?? $shift->starts_at,
                    $schedule->ends_at ?? $shift->ends_at,
                );
            });
    }

    private function scheduleCovers(StaffSchedule $schedule, Carbon $start, Carbon $end): bool
    {
        if ($schedule->starts_at !== null && $schedule->ends_at !== null) {
            return $this->covers($start, $end, $schedule->starts_at, $schedule->ends_at);
        }

        $shift = $schedule->workShift;

        if ($shift === null || ! $shift->is_active) {
            return false;
        }

        return $this->covers($start, $end, $shift->starts_at, $shift->ends_at);
    }

    private function resourceBusy(ResourceType $type, int $resourceId, Carbon $start, Carbon $end, ?int $ignoreAppointmentId): bool
    {
        return AppointmentResourceAllocation::query()
            ->where('resource_type', $type->value)
            ->where('resource_id', $resourceId)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->whereHas('appointment', function ($query) use ($ignoreAppointmentId): void {
                $query->whereIn('status', AppointmentStatus::occupyingValues());

                if ($ignoreAppointmentId !== null) {
                    $query->whereKeyNot($ignoreAppointmentId);
                }
            })
            ->exists();
    }

    private function patientBusy(int $patientId, Carbon $start, Carbon $end, ?int $ignoreAppointmentId): bool
    {
        return Appointment::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', AppointmentStatus::occupyingValues())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($ignoreAppointmentId !== null, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->exists();
    }

    /**
     * @return Collection<int, WorkShift>
     */
    private function activeShifts(Carbon $day): Collection
    {
        return WorkShift::query()
            ->where('is_active', true)
            ->where('weekday', $day->dayOfWeek)
            ->with('breaks')
            ->orderBy('starts_at')
            ->get();
    }

    private function closedHoliday(Carbon $day): ?Holiday
    {
        return Holiday::query()
            ->whereDate('holiday_date', $day->toDateString())
            ->where('is_closed', true)
            ->first();
    }

    private function overlapsBreak(WorkShift $shift, Carbon $start, Carbon $end): bool
    {
        return $shift->breaks->contains(fn ($break): bool => $this->overlaps($start, $end, $break->starts_at, $break->ends_at));
    }

    private function covers(Carbon $start, Carbon $end, mixed $clockStart, mixed $clockEnd): bool
    {
        $windowStart = $this->onDate($start, $clockStart);
        $windowEnd = $this->onDate($start, $clockEnd);

        return $start->greaterThanOrEqualTo($windowStart) && $end->lessThanOrEqualTo($windowEnd);
    }

    private function overlaps(Carbon $start, Carbon $end, mixed $clockStart, mixed $clockEnd): bool
    {
        $windowStart = $this->onDate($start, $clockStart);
        $windowEnd = $this->onDate($start, $clockEnd);

        return $windowStart->lessThan($end) && $windowEnd->greaterThan($start);
    }

    private function onDate(Carbon $day, mixed $clock): Carbon
    {
        return Carbon::parse($day->toDateString().' '.ClockInput::normalize((string) $clock), config('app.timezone'));
    }
}
