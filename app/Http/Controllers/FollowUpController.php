<?php

namespace App\Http\Controllers;

use App\Actions\Clinical\CreateFollowUpRequest;
use App\Actions\Clinical\DeclineFollowUp;
use App\Actions\Clinical\MarkFollowUpOffered;
use App\Enums\FollowUpStatus;
use App\Enums\UserRole;
use App\Exceptions\Clinical\InvalidTreatmentTransitionException;
use App\Http\Requests\StoreFollowUpRequest;
use App\Http\Requests\UpdateFollowUpOperationalRequest;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\TreatmentService;
use App\Support\JalaliCalendar;
use App\Support\SpecialistPatientScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class FollowUpController extends Controller
{
    public function index(Request $request, JalaliCalendar $calendar): View
    {
        $this->authorize('viewAny', FollowUp::class);

        $user = $request->user();
        $actionable = $request->boolean('actionable', $user->hasRole(UserRole::Admin, UserRole::Receptionist));

        $followUps = FollowUp::query()
            ->with(['patient:id,first_name,last_name,code,mobile', 'treatmentService:id,name', 'createdBy:id,name'])
            ->when(
                $actionable,
                fn ($query) => $query->whereIn('status', [
                    FollowUpStatus::Proposed->value,
                    FollowUpStatus::OfferedToPatient->value,
                    FollowUpStatus::Accepted->value,
                ]),
            )
            ->when(
                SpecialistPatientScope::isSpecialistOnly($user),
                fn ($query) => $query->where(function ($query) use ($user): void {
                    $query->where('created_by', $user->id)
                        ->orWhereHas(
                            'patient.appointments.allocations',
                            fn ($allocation) => $allocation
                                ->where('resource_type', \App\Enums\ResourceType::Specialist->value)
                                ->where('resource_id', $user->id),
                        );
                }),
            )
            ->orderBy('proposed_window_start')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.follow-ups.index', [
            'followUps' => $followUps,
            'calendar' => $calendar,
            'actionable' => $actionable,
            'canSchedule' => $user->hasRole(UserRole::Admin, UserRole::Receptionist),
        ]);
    }

    public function create(Request $request, Patient $patient, JalaliCalendar $calendar): View
    {
        $this->authorize('view', $patient);
        $this->authorize('create', FollowUp::class);

        return view('clinical.follow-ups.create', [
            'patient' => $patient,
            'visitId' => $request->integer('visit_id') ?: null,
            'services' => TreatmentService::query()->where('is_active', true)->orderBy('name')->get(),
            'calendar' => $calendar,
        ]);
    }

    public function store(
        StoreFollowUpRequest $request,
        Patient $patient,
        CreateFollowUpRequest $create,
    ): RedirectResponse {
        $this->authorize('view', $patient);

        $payload = $request->validated();
        $payload['proposed_window_start'] = $payload['proposed_window_start_gregorian'];
        $payload['proposed_window_end'] = $payload['proposed_window_end_gregorian'];

        try {
            $followUp = $create->handle($patient, $request->user(), $payload);
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['proposed_window_start' => $exception->getMessage()]);
        }

        return redirect()
            ->route('clinical.follow-ups.show', $followUp)
            ->with('status', 'درخواست پیگیری ثبت شد. نوبت قطعی ساخته نشد.');
    }

    public function show(FollowUp $followUp, JalaliCalendar $calendar): View
    {
        $this->authorize('view', $followUp);

        $followUp->load(['patient', 'treatmentService', 'createdBy:id,name', 'convertedAppointment']);

        return view('clinical.follow-ups.show', [
            'followUp' => $followUp,
            'calendar' => $calendar,
            'canSchedule' => request()->user()->can('schedule', $followUp),
            'canOperate' => request()->user()->can('updateOperational', $followUp),
        ]);
    }

    public function markOffered(
        UpdateFollowUpOperationalRequest $request,
        FollowUp $followUp,
        MarkFollowUpOffered $action,
    ): RedirectResponse {
        try {
            $action->handle($followUp, $request->user(), $request->validated('outcome_note') ?? null);
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidTreatmentTransitionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('status', 'پیگیری به‌عنوان پیشنهاد داده‌شده به بیمار علامت خورد. نوبت قطعی ساخته نشد.');
    }

    public function decline(
        UpdateFollowUpOperationalRequest $request,
        FollowUp $followUp,
        DeclineFollowUp $action,
    ): RedirectResponse {
        try {
            $action->handle($followUp, $request->user(), $request->validated('outcome_note') ?? null);
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidTreatmentTransitionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('status', 'پیگیری رد شد و نوبتی ساخته نشد.');
    }
}
