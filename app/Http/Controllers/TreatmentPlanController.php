<?php

namespace App\Http\Controllers;

use App\Actions\Clinical\AssignTreatmentTask;
use App\Actions\Clinical\SaveTreatmentPlan;
use App\Enums\Qualification;
use App\Enums\StaffType;
use App\Enums\TreatmentPlanStatus;
use App\Enums\UserRole;
use App\Exceptions\Clinical\UnqualifiedAssigneeException;
use App\Http\Requests\StoreTreatmentPlanRequest;
use App\Http\Requests\StoreTreatmentTaskRequest;
use App\Http\Requests\UpdateTreatmentPlanRequest;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Support\JalaliCalendar;
use App\Support\SpecialistPatientScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreatmentPlanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TreatmentPlan::class);

        $user = $request->user();
        $plans = TreatmentPlan::query()
            ->with(['patient:id,first_name,last_name,code', 'createdBy:id,name'])
            ->when(
                SpecialistPatientScope::isSpecialistOnly($user),
                fn ($query) => $query->where(function ($query) use ($user): void {
                    $query->where('created_by_user_id', $user->id)
                        ->orWhereHas(
                            'patient.appointments.allocations',
                            fn ($allocation) => $allocation
                                ->where('resource_type', \App\Enums\ResourceType::Specialist->value)
                                ->where('resource_id', $user->id),
                        );
                }),
            )
            ->orderByDesc('id')
            ->paginate(20);

        return view('clinical.plans.index', [
            'plans' => $plans,
            'canSeeClinical' => $user->hasRole(UserRole::Admin, UserRole::Specialist),
        ]);
    }

    public function create(Request $request, Patient $patient): View
    {
        $this->authorize('view', $patient);
        $this->authorize('create', TreatmentPlan::class);

        return view('clinical.plans.create', [
            'patient' => $patient,
            'visitId' => $request->integer('visit_id') ?: null,
            'statuses' => TreatmentPlanStatus::cases(),
        ]);
    }

    public function store(
        StoreTreatmentPlanRequest $request,
        Patient $patient,
        SaveTreatmentPlan $save,
    ): RedirectResponse {
        $this->authorize('view', $patient);

        try {
            $plan = $save->create($patient, $request->user(), $request->validated());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        return redirect()
            ->route('clinical.plans.show', $plan)
            ->with('status', 'برنامه درمانی ثبت شد.');
    }

    public function show(TreatmentPlan $plan, JalaliCalendar $calendar): View
    {
        $this->authorize('view', $plan);

        $plan->load([
            'patient',
            'createdBy:id,name',
            'visit.appointment.treatmentService',
            'tasks.assignee:id,name',
        ]);

        $assignees = User::query()
            ->where('role', UserRole::ClinicalStaff)
            ->where('is_active', true)
            ->whereHas('staffProfile', fn ($query) => $query->where('is_active', true)->where('staff_type', StaffType::ClinicalStaff))
            ->with('staffProfile')
            ->orderBy('name')
            ->get();

        return view('clinical.plans.show', [
            'plan' => $plan,
            'calendar' => $calendar,
            'canSeeClinical' => request()->user()->can('viewClinicalSummary', $plan),
            'canEdit' => request()->user()->can('update', $plan),
            'canAssign' => request()->user()->can('create', \App\Models\TreatmentTask::class),
            'assignees' => $assignees,
            'qualifications' => Qualification::cases(),
            'statuses' => TreatmentPlanStatus::cases(),
        ]);
    }

    public function update(
        UpdateTreatmentPlanRequest $request,
        TreatmentPlan $plan,
        SaveTreatmentPlan $save,
    ): RedirectResponse {
        try {
            $save->update($plan, $request->user(), $request->validated());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        return back()->with('status', 'برنامه درمانی به‌روزرسانی شد.');
    }

    public function storeTask(
        StoreTreatmentTaskRequest $request,
        TreatmentPlan $plan,
        AssignTreatmentTask $assign,
    ): RedirectResponse {
        try {
            $assign->create($plan, $request->user(), $request->validated());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (UnqualifiedAssigneeException $exception) {
            return back()->withInput()->withErrors(['assignee_user_id' => $exception->getMessage()]);
        }

        return back()->with('status', 'وظیفه درمانی ثبت شد.');
    }
}
