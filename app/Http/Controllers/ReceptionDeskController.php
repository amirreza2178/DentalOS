<?php

namespace App\Http\Controllers;

use App\Actions\Reception\CallNextInQueue;
use App\Actions\Reception\CheckInAppointment;
use App\Actions\Reception\CompleteVisit;
use App\Actions\Reception\RemoveFromQueue;
use App\Actions\Reception\StartVisitService;
use App\Enums\AppointmentStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Exceptions\Reception\InvalidVisitTransitionException;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\WaitingQueueEntry;
use App\Support\ClinicDay;
use App\Support\ClinicIdentity;
use App\Support\JalaliCalendar;
use App\Support\ReceptionCommandCenter;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceptionDeskController extends Controller
{
    public function day(
        Request $request,
        JalaliCalendar $calendar,
        ClinicDay $clinicDay,
        ReceptionCommandCenter $commandCenter,
        ClinicIdentity $clinicIdentity,
    ): View {
        $this->authorize('view', 'reception');

        [$gregorian, $dateInput, $dateError] = $this->resolveDate($request, $calendar, $clinicDay);
        $board = $commandCenter->build($request->user(), $gregorian, $clinicDay);

        return view('reception.day', array_merge($board, [
            'date' => $dateInput,
            'dateError' => $dateError,
            'gregorian' => $gregorian,
            'calendar' => $calendar,
            'clinicDay' => $clinicDay,
            'timezoneLabel' => $clinicDay->timezone(),
            'clinicIdentity' => $clinicIdentity,
            'autoRefreshSeconds' => $board['canOperate'] ? 90 : null,
        ]));
    }

    public function week(Request $request, JalaliCalendar $calendar, ClinicDay $clinicDay): View
    {
        $this->authorize('view', 'reception');

        [$gregorian, $dateInput, $dateError] = $this->resolveDate($request, $calendar, $clinicDay);
        [$weekStart, $weekEnd] = $clinicDay->weekBounds($gregorian);
        $rangeStart = $clinicDay->startOfDay($weekStart);
        $rangeEnd = $clinicDay->endExclusive($weekEnd);

        $user = $request->user();
        $specialistOnly = $user->hasRole(UserRole::Specialist) && ! $user->hasRole(UserRole::Admin, UserRole::Receptionist);

        $appointments = Appointment::query()
            ->with([
                'patient:id,first_name,last_name,code',
                'treatmentService:id,name',
                'visit',
            ])
            ->where('starts_at', '>=', $rangeStart)
            ->where('starts_at', '<', $rangeEnd)
            ->whereNotIn('status', [AppointmentStatus::Draft->value])
            ->when(
                $specialistOnly,
                fn ($query) => $query->whereHas(
                    'allocations',
                    fn ($allocation) => $allocation
                        ->where('resource_type', ResourceType::Specialist->value)
                        ->where('resource_id', $user->id),
                ),
            )
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->starts_at->timezone($clinicDay->timezone())->toDateString());

        $days = [];
        for ($cursor = Carbon::parse($weekStart, $clinicDay->timezone()); $cursor->toDateString() <= $weekEnd; $cursor->addDay()) {
            $key = $cursor->toDateString();
            $days[] = [
                'gregorian' => $key,
                'jalali' => $calendar->formatDate($key),
                'appointments' => $appointments->get($key, collect()),
            ];
        }

        $prev = Carbon::parse($weekStart, $clinicDay->timezone())->subWeek()->toDateString();
        $next = Carbon::parse($weekStart, $clinicDay->timezone())->addWeek()->toDateString();

        return view('reception.week', [
            'date' => $dateInput,
            'dateError' => $dateError,
            'gregorian' => $gregorian,
            'days' => $days,
            'weekStartJalali' => $calendar->formatDate($weekStart),
            'weekEndJalali' => $calendar->formatDate($weekEnd),
            'prevDate' => $calendar->formatDate($prev),
            'nextDate' => $calendar->formatDate($next),
            'calendar' => $calendar,
            'clinicDay' => $clinicDay,
            'timezoneLabel' => $clinicDay->timezone(),
        ]);
    }

    public function search(Request $request, JalaliCalendar $calendar, ClinicDay $clinicDay): View
    {
        $this->authorize('operate', 'reception');

        $term = trim($request->string('q')->toString());
        [$gregorian, $dateInput] = $this->resolveDate($request, $calendar, $clinicDay);

        $patients = collect();
        if ($term !== '') {
            $patients = Patient::query()
                ->matchingSearch($term)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(20)
                ->get(['id', 'code', 'first_name', 'last_name', 'mobile', 'status']);
        }

        $dayStart = $clinicDay->startOfDay($gregorian);
        $dayEnd = $clinicDay->endExclusive($gregorian);

        $appointmentsByPatient = Appointment::query()
            ->with(['treatmentService:id,name', 'visit.latestQueueEntry'])
            ->whereIn('patient_id', $patients->pluck('id'))
            ->where('starts_at', '>=', $dayStart)
            ->where('starts_at', '<', $dayEnd)
            ->orderBy('starts_at')
            ->get()
            ->groupBy('patient_id');

        return view('reception.search', [
            'term' => $term,
            'date' => $dateInput,
            'gregorian' => $gregorian,
            'patients' => $patients,
            'appointmentsByPatient' => $appointmentsByPatient,
            'calendar' => $calendar,
            'clinicDay' => $clinicDay,
            'canOperate' => $request->user()->can('operate', 'reception'),
        ]);
    }

    public function showVisit(PatientVisit $visit, JalaliCalendar $calendar, ClinicDay $clinicDay): View
    {
        $this->authorize('view', $visit);

        $visit->load([
            'patient',
            'appointment.treatmentService',
            'appointment.allocations',
            'checkedInBy:id,name',
            'queueEntries',
        ]);

        $user = request()->user();

        return view('reception.visit', [
            'visit' => $visit,
            'calendar' => $calendar,
            'clinicDay' => $clinicDay,
            'canOperate' => $user->can('operate', 'reception'),
            'canComplete' => $user->can('complete', $visit),
            'canCreatePlan' => $user->can('create', \App\Models\TreatmentPlan::class),
            'canCreateFollowUp' => $user->can('create', \App\Models\FollowUp::class),
            'plans' => $visit->patient
                ? $visit->patient->treatmentPlans()->with('tasks')->orderByDesc('id')->limit(5)->get()
                : collect(),
        ]);
    }

    public function checkIn(
        Request $request,
        Appointment $appointment,
        CheckInAppointment $checkIn,
        JalaliCalendar $calendar,
    ): RedirectResponse {
        $this->authorize('checkIn', $appointment);

        try {
            $result = $checkIn->handle($appointment, $request->user());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (ReceptionConflictException|InvalidAppointmentTransitionException $exception) {
            return back()->withErrors(['check_in' => $exception->getMessage()]);
        }

        $message = $result['created']
            ? 'چک‌این انجام شد و بیمار به صف انتظار اضافه شد.'
            : 'این بیمار قبلاً چک‌این شده بود. صف فعلی به‌روز است.';

        if ($request->boolean('open_visit')) {
            return redirect()
                ->route('reception.visits.show', $result['visit'])
                ->with('status', $message);
        }

        $day = $calendar->formatDate(
            $appointment->starts_at->timezone(config('app.timezone'))->toDateString()
        );

        return redirect()
            ->route('reception.day', ['date' => $day])
            ->with('status', $message)
            ->withFragment('day-queue-heading');
    }

    public function callNext(Request $request, CallNextInQueue $callNext, JalaliCalendar $calendar, ClinicDay $clinicDay): RedirectResponse
    {
        $this->authorize('callNext', WaitingQueueEntry::class);

        [$gregorian] = $this->resolveDate($request, $calendar, $clinicDay);

        try {
            $entry = $callNext->handle($gregorian, $request->user());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (ReceptionConflictException $exception) {
            return back()->withErrors(['queue' => $exception->getMessage()]);
        }

        return redirect()
            ->route('reception.day', ['date' => $calendar->formatDate($gregorian)])
            ->with('status', 'بیمار «'.$entry->visit->patient->fullName().'» فراخوانده شد.');
    }

    public function startVisit(PatientVisit $visit, StartVisitService $start): RedirectResponse
    {
        $this->authorize('start', $visit);

        try {
            $start->handle($visit, request()->user());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (ReceptionConflictException|InvalidVisitTransitionException $exception) {
            return back()->withErrors(['visit' => $exception->getMessage()]);
        }

        return back()->with('status', 'ویزیت شروع شد.');
    }

    public function completeVisit(PatientVisit $visit, CompleteVisit $complete): RedirectResponse
    {
        $this->authorize('complete', $visit);

        try {
            $complete->handle($visit, request()->user());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (ReceptionConflictException|InvalidVisitTransitionException $exception) {
            return back()->withErrors(['visit' => $exception->getMessage()]);
        }

        return back()->with('status', 'ویزیت تمام شد و از صف فعال خارج شد.');
    }

    public function removeQueueEntry(WaitingQueueEntry $entry, RemoveFromQueue $remove): RedirectResponse
    {
        $this->authorize('remove', $entry);

        try {
            $remove->handle($entry, request()->user());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (ReceptionConflictException $exception) {
            return back()->withErrors(['queue' => $exception->getMessage()]);
        }

        return back()->with('status', 'بیمار از صف خارج شد.');
    }

    /**
     * @return array{0: string, 1: string, 2: ?string}
     */
    private function resolveDate(Request $request, JalaliCalendar $calendar, ClinicDay $clinicDay): array
    {
        $dateInput = trim($request->string('date')->toString());
        $dateError = null;

        if ($dateInput === '') {
            $today = now($clinicDay->timezone())->toDateString();

            return [$today, (string) $calendar->formatDate($today), null];
        }

        try {
            $gregorian = $calendar->parseToGregorianDate($dateInput);
            if ($gregorian === null) {
                throw new \InvalidArgumentException('empty');
            }

            return [$gregorian, $dateInput, null];
        } catch (\InvalidArgumentException) {
            $today = now($clinicDay->timezone())->toDateString();

            return [$today, $dateInput, 'تاریخ را با قالب جلالی صحیح وارد کنید.'];
        }
    }
}
