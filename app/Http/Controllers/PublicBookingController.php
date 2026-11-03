<?php

namespace App\Http\Controllers;

use App\Actions\Booking\BookOnlineAppointment;
use App\Actions\Scheduling\AvailabilityService;
use App\Exceptions\Scheduling\SlotUnavailableException;
use App\Http\Requests\StoreOnlineBookingRequest;
use App\Models\Appointment;
use App\Models\TreatmentService;
use App\Support\JalaliCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;

class PublicBookingController extends Controller
{
    public function welcome(): View
    {
        return view('booking.welcome');
    }

    public function services(): View
    {
        $services = TreatmentService::query()
            ->where('is_active', true)
            ->where('is_bookable_online', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'default_duration_minutes', 'buffer_minutes']);

        return view('booking.services', [
            'services' => $services,
            'step' => 1,
        ]);
    }

    public function service(TreatmentService $service, JalaliCalendar $calendar): View|RedirectResponse
    {
        if (! $service->is_active || ! $service->is_bookable_online) {
            return redirect()
                ->route('booking.services')
                ->withErrors(['service' => 'این خدمت برای رزرو آنلاین در دسترس نیست.']);
        }

        return view('booking.date', [
            'service' => $service,
            'calendar' => $calendar,
            'step' => 2,
            'todayJalali' => $calendar->formatDate(now(config('app.timezone'))),
        ]);
    }

    public function slots(Request $request, TreatmentService $service, AvailabilityService $availability, JalaliCalendar $calendar): View|RedirectResponse
    {
        if (! $service->is_active || ! $service->is_bookable_online) {
            return redirect()
                ->route('booking.services')
                ->withErrors(['service' => 'این خدمت برای رزرو آنلاین در دسترس نیست.']);
        }

        $dateInput = trim($request->string('date')->toString());
        $gregorian = $this->gregorianDate($dateInput, $calendar);
        $dateError = $dateInput === '' || $gregorian === null
            ? 'تاریخ را با قالب جلالی صحیح وارد کنید.'
            : null;

        $slots = [];
        if ($gregorian !== null) {
            $slots = $availability->slots($service, $gregorian);
        }

        return view('booking.slots', [
            'service' => $service,
            'calendar' => $calendar,
            'date' => $dateInput,
            'dateError' => $dateError,
            'gregorian' => $gregorian,
            'slots' => $slots,
            'step' => 3,
        ]);
    }

    public function form(Request $request, TreatmentService $service, AvailabilityService $availability, JalaliCalendar $calendar): View|RedirectResponse
    {
        if (! $service->is_active || ! $service->is_bookable_online) {
            return redirect()
                ->route('booking.services')
                ->withErrors(['service' => 'این خدمت برای رزرو آنلاین در دسترس نیست.']);
        }

        $dateInput = trim($request->string('date')->toString());
        $timeInput = trim($request->string('time')->toString());
        $gregorian = $this->gregorianDate($dateInput, $calendar);

        if ($gregorian === null || $timeInput === '') {
            return redirect()
                ->route('booking.slots', ['service' => $service, 'date' => $dateInput])
                ->withErrors(['appointment_time' => 'زمان معتبر انتخاب کنید.']);
        }

        try {
            $starts = Carbon::parse($gregorian.' '.$timeInput, config('app.timezone'));
        } catch (\Carbon\Exceptions\InvalidFormatException) {
            return redirect()
                ->route('booking.slots', ['service' => $service, 'date' => $dateInput])
                ->withErrors(['appointment_time' => 'ساعت انتخاب‌شده معتبر نیست.']);
        }

        $opening = $availability->opening($service, $starts);
        if ($opening === null) {
            return redirect()
                ->route('booking.slots', ['service' => $service, 'date' => $dateInput])
                ->withErrors(['appointment_time' => 'این زمان دیگر آزاد نیست. زمان دیگری انتخاب کنید.']);
        }

        return view('booking.form', [
            'service' => $service,
            'calendar' => $calendar,
            'date' => $dateInput,
            'time' => $opening->startsAt->format('H:i'),
            'timeValue' => $opening->startsAt->format('H:i:s'),
            'gregorian' => $gregorian,
            'endsAt' => $opening->endsAt,
            'idempotencyKey' => (string) Str::uuid(),
            'step' => 4,
        ]);
    }

    public function store(StoreOnlineBookingRequest $request, BookOnlineAppointment $booker, JalaliCalendar $calendar): RedirectResponse
    {
        try {
            $appointment = $booker->book([
                'first_name' => $request->validated('first_name'),
                'last_name' => $request->validated('last_name'),
                'mobile' => $request->validated('mobile'),
                'treatment_service_id' => (int) $request->validated('treatment_service_id'),
                'starts_at' => Carbon::parse(
                    $request->validated('appointment_date_gregorian').' '.$request->validated('appointment_time'),
                    config('app.timezone'),
                ),
                'patient_notes' => $request->validated('patient_notes'),
                'idempotency_key' => $request->validated('idempotency_key'),
            ]);
        } catch (SlotUnavailableException $exception) {
            return back()
                ->withInput()
                ->withErrors(['appointment_time' => $exception->reasons]);
        }

        return redirect()->route('booking.confirmation', $appointment->public_reference);
    }

    public function confirmation(string $reference, JalaliCalendar $calendar): View|Response
    {
        // UUID-only lookup; never expose sequential IDs or clinical/personal surplus.
        $appointment = Appointment::query()
            ->where('public_reference', $reference)
            ->with(['treatmentService:id,name'])
            ->first();

        if ($appointment === null) {
            return response()->view('booking.confirmation', [
                'found' => false,
                'reference' => $reference,
                'statusLabel' => null,
                'serviceName' => null,
                'whenLabel' => null,
                'step' => 5,
            ], 404);
        }

        return view('booking.confirmation', [
            'found' => true,
            'reference' => $appointment->public_reference,
            'statusLabel' => $appointment->status->label(),
            'serviceName' => $appointment->treatmentService?->name,
            'whenLabel' => $calendar->formatDate($appointment->starts_at)
                .' — '
                .$appointment->starts_at->timezone(config('app.timezone'))->format('H:i'),
            'step' => 5,
        ]);
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
