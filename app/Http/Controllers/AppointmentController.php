<?php

namespace App\Http\Controllers;

use App\Actions\Scheduling\AvailabilityService;
use App\Actions\Scheduling\CancelAppointment;
use App\Actions\Scheduling\ReservationData;
use App\Actions\Scheduling\RescheduleAppointment;
use App\Actions\Scheduling\ReserveAppointment;
use App\Enums\AppointmentSource;
use App\Enums\PatientStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Exceptions\Scheduling\SlotUnavailableException;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Patient;
use App\Models\TreatmentService;
use App\Models\User;
use App\Support\JalaliCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

class AppointmentController extends Controller
{
    public function index(Request $request, JalaliCalendar $calendar): View
    {
        $this->authorize('viewAny', Appointment::class);

        $dateInput = trim($request->string('date')->toString());
        $gregorian = $this->gregorianDate($dateInput, $calendar);
        $dateError = $dateInput !== '' && $gregorian === null
            ? 'تاریخ را با قالب جلالی صحیح وارد کنید.'
            : null;

        $user = $request->user();

        $appointments = Appointment::query()
            ->with(['patient', 'treatmentService'])
            ->when($gregorian !== null, fn ($query) => $query->whereDate('starts_at', $gregorian))
            ->when(
                $user !== null && $user->hasRole(UserRole::Specialist) && ! $user->hasRole(UserRole::Admin, UserRole::Receptionist),
                fn ($query) => $query->whereHas(
                    'allocations',
                    fn ($allocation) => $allocation
                        ->where('resource_type', ResourceType::Specialist->value)
                        ->where('resource_id', $user->id),
                ),
            )
            ->orderBy('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'date' => $dateInput,
            'dateError' => $dateError,
            'calendar' => $calendar,
            'specialistScoped' => $user !== null && $user->hasRole(UserRole::Specialist) && ! $user->hasRole(UserRole::Admin, UserRole::Receptionist),
        ]);
    }

    public function create(Request $request, AvailabilityService $availability, JalaliCalendar $calendar): View
    {
        $this->authorize('create', Appointment::class);

        return view('appointments.create', $this->bookingForm($request, $availability, $calendar));
    }

    public function store(StoreAppointmentRequest $request, ReserveAppointment $reserve): RedirectResponse
    {
        try {
            $appointment = $reserve->reserve($this->intent($request));
        } catch (SlotUnavailableException $exception) {
            return back()->withInput()->withErrors(['appointment_time' => $exception->reasons]);
        }

        return redirect()
            ->route('appointments.show', $appointment)
            ->with('status', 'نوبت ثبت شد.');
    }

    public function show(Appointment $appointment, JalaliCalendar $calendar): View
    {
        $this->authorize('view', $appointment);
        $appointment->load([
            'patient',
            'treatmentService',
            'allocations',
            'bookedBy',
            'rescheduledFrom',
            'rescheduledTo',
            'visit',
        ]);

        $people = User::query()
            ->whereIn('id', $appointment->allocations->pluck('resource_id'))
            ->get()
            ->keyBy('id');
        $chairs = Chair::query()
            ->whereIn('id', $appointment->allocations->pluck('resource_id'))
            ->get()
            ->keyBy('id');

        return view('appointments.show', [
            'appointment' => $appointment,
            'calendar' => $calendar,
            'people' => $people,
            'chairs' => $chairs,
        ]);
    }

    public function cancel(Appointment $appointment, CancelAppointment $cancel): RedirectResponse
    {
        $this->authorize('cancel', $appointment);

        try {
            $cancel->cancel($appointment);
        } catch (InvalidAppointmentTransitionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()
            ->route('appointments.show', $appointment)
            ->with('status', 'نوبت لغو شد و منابعش برای رزرو بعدی آزاد شد.');
    }

    public function reschedule(Request $request, Appointment $appointment, AvailabilityService $availability, JalaliCalendar $calendar): View
    {
        $this->authorize('reschedule', $appointment);

        if (! $request->filled('service_id')) {
            $request->merge(['service_id' => $appointment->treatment_service_id]);
        }

        return view('appointments.reschedule', array_merge(
            $this->bookingForm($request, $availability, $calendar, $appointment->patient_id),
            ['appointment' => $appointment->load('patient', 'treatmentService')],
        ));
    }

    public function storeReschedule(RescheduleAppointmentRequest $request, Appointment $appointment, RescheduleAppointment $reschedule): RedirectResponse
    {
        $intent = $this->intent($request);
        $intent = new ReservationData(
            patientId: $appointment->patient_id,
            treatmentServiceId: $intent->treatmentServiceId,
            startsAt: $intent->startsAt,
            specialistId: $intent->specialistId,
            clinicalStaffIds: $intent->clinicalStaffIds,
            chairId: $intent->chairId,
            source: AppointmentSource::Receptionist,
            actorUserId: $intent->actorUserId,
            patientNotes: $intent->patientNotes,
        );

        try {
            $created = $reschedule->reschedule($appointment, $intent);
        } catch (SlotUnavailableException $exception) {
            return back()->withInput()->withErrors(['appointment_time' => $exception->reasons]);
        } catch (InvalidAppointmentTransitionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()
            ->route('appointments.show', $created)
            ->with('status', 'نوبت جدید ثبت شد و نوبت قبلی دیگر منبعی را اشغال نمی‌کند.');
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingForm(Request $request, AvailabilityService $availability, JalaliCalendar $calendar, ?int $lockedPatientId = null): array
    {
        $term = trim($request->string('q')->toString());
        $patientId = $lockedPatientId ?? ($request->integer('patient_id') ?: null);
        $serviceId = $request->integer('service_id') ?: null;
        $dateInput = trim($request->string('date')->toString());
        $timeInput = trim($request->string('time')->toString());
        $gregorian = $this->gregorianDate($dateInput, $calendar);
        $dateError = $dateInput !== '' && $gregorian === null ? 'تاریخ را با قالب جلالی صحیح وارد کنید.' : null;

        $patients = $term === ''
            ? collect()
            : Patient::query()->where('status', PatientStatus::Active)->matchingSearch($term)->orderBy('last_name')->limit(15)->get();

        $patient = $patientId === null ? null : Patient::query()->where('status', PatientStatus::Active)->find($patientId);
        $services = TreatmentService::query()->where('is_active', true)->orderBy('name')->get();
        $service = $serviceId === null ? null : $services->firstWhere('id', $serviceId);
        $slots = [];
        $reasons = [];
        $opening = null;

        if ($patient !== null && $service !== null && $gregorian !== null) {
            $slots = $availability->slots($service, $gregorian, $patient->id);
        }

        if ($patient !== null && $service !== null && $gregorian !== null && $timeInput !== '') {
            try {
                $starts = Carbon::parse($gregorian.' '.$timeInput, config('app.timezone'));
                $opening = $availability->opening($service, $starts, $patient->id);

                if ($opening === null) {
                    $reasons = $availability->reasons(new ReservationData(
                        patientId: $patient->id,
                        treatmentServiceId: $service->id,
                        startsAt: $starts,
                        specialistId: null,
                        clinicalStaffIds: [],
                        chairId: null,
                        source: AppointmentSource::Receptionist,
                        actorUserId: $request->user()?->id,
                    ));
                }
            } catch (\Carbon\Exceptions\InvalidFormatException) {
                $reasons = ['ساعت را با قالب ساعت:دقیقه وارد کنید.'];
            }
        }

        $peopleIds = collect($slots)
            ->flatMap(fn ($slot) => array_merge($slot->specialistIds, $slot->clinicalStaffIds))
            ->merge($opening?->specialistIds ?? [])
            ->merge($opening?->clinicalStaffIds ?? [])
            ->unique()
            ->all();
        $chairIds = collect($slots)
            ->flatMap(fn ($slot) => $slot->chairIds)
            ->merge($opening?->chairIds ?? [])
            ->unique()
            ->all();

        return [
            'term' => $term,
            'patients' => $patients,
            'patient' => $patient,
            'services' => $services,
            'service' => $service,
            'date' => $dateInput,
            'time' => $timeInput,
            'dateError' => $dateError,
            'slots' => $slots,
            'opening' => $opening,
            'reasons' => $reasons,
            'people' => User::query()->whereIn('id', $peopleIds)->orderBy('name')->get()->keyBy('id'),
            'chairs' => Chair::query()->whereIn('id', $chairIds)->orderBy('sort_order')->get()->keyBy('id'),
            'calendar' => $calendar,
            'lockedPatient' => $lockedPatientId !== null,
        ];
    }

    private function intent(StoreAppointmentRequest $request): ReservationData
    {
        return new ReservationData(
            patientId: (int) $request->validated('patient_id'),
            treatmentServiceId: (int) $request->validated('treatment_service_id'),
            startsAt: Carbon::parse(
                $request->validated('appointment_date_gregorian').' '.$request->validated('appointment_time'),
                config('app.timezone'),
            ),
            specialistId: $request->validated('specialist_id') !== null ? (int) $request->validated('specialist_id') : null,
            clinicalStaffIds: array_map(intval(...), $request->validated('clinical_staff_ids') ?? []),
            chairId: $request->validated('chair_id') !== null ? (int) $request->validated('chair_id') : null,
            source: AppointmentSource::Receptionist,
            actorUserId: $request->user()?->id,
            patientNotes: $request->validated('patient_notes'),
        );
    }

    private function gregorianDate(string $input, JalaliCalendar $calendar): ?string
    {
        if ($input === '') {
            return null;
        }

        try {
            return $calendar->parseToGregorianDate($input);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
