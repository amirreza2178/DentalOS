<?php

namespace App\Http\Controllers;

use App\Actions\Clinical\AssignTreatmentTask;
use App\Actions\Clinical\UpdateTreatmentTaskStatus;
use App\Enums\StaffType;
use App\Enums\TreatmentTaskStatus;
use App\Enums\UserRole;
use App\Exceptions\Clinical\InvalidTreatmentTransitionException;
use App\Exceptions\Clinical\UnqualifiedAssigneeException;
use App\Http\Requests\AssignTreatmentTaskRequest;
use App\Http\Requests\UpdateTreatmentTaskStatusRequest;
use App\Models\TreatmentTask;
use App\Models\User;
use App\Support\JalaliCalendar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreatmentTaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TreatmentTask::class);

        $user = $request->user();
        $mineOnly = $user->hasRole(UserRole::ClinicalStaff) && ! $user->hasRole(UserRole::Admin, UserRole::Specialist);

        $tasks = TreatmentTask::query()
            ->with([
                'plan.patient:id,first_name,last_name,code',
                'assignee:id,name',
            ])
            ->when($mineOnly, fn ($query) => $query->where('assignee_user_id', $user->id))
            ->when(
                $user->hasRole(UserRole::Specialist) && ! $user->hasRole(UserRole::Admin),
                fn ($query) => $query->whereHas(
                    'plan',
                    fn ($plan) => $plan->where('created_by_user_id', $user->id)
                        ->orWhereHas(
                            'patient.appointments.allocations',
                            fn ($allocation) => $allocation
                                ->where('resource_type', \App\Enums\ResourceType::Specialist->value)
                                ->where('resource_id', $user->id),
                        ),
                ),
            )
            ->whereNotIn('status', [TreatmentTaskStatus::Cancelled->value])
            ->orderByRaw("CASE status WHEN 'assigned' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(30);

        return view('clinical.tasks.index', [
            'tasks' => $tasks,
            'mineOnly' => $mineOnly,
        ]);
    }

    public function show(TreatmentTask $task, JalaliCalendar $calendar): View
    {
        $this->authorize('view', $task);

        $task->load([
            'plan.patient:id,first_name,last_name,code',
            'plan.createdBy:id,name',
            'assignee:id,name',
            'assignedBy:id,name',
        ]);

        $assignees = User::query()
            ->where('role', UserRole::ClinicalStaff)
            ->where('is_active', true)
            ->whereHas(
                'staffProfile',
                fn ($query) => $query
                    ->where('is_active', true)
                    ->where('staff_type', StaffType::ClinicalStaff)
                    ->where('qualification', $task->required_qualification->value),
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('clinical.tasks.show', [
            'task' => $task,
            'calendar' => $calendar,
            'assignees' => $assignees,
            'canAssign' => request()->user()->can('assign', $task),
            'canUpdateStatus' => request()->user()->can('updateStatus', $task),
            'canCancel' => request()->user()->can('cancel', $task),
            'canSeeClinical' => request()->user()->can('viewClinicalSummary', $task->plan),
            'nextStatuses' => $task->status->allowedTransitions(),
        ]);
    }

    public function assign(
        AssignTreatmentTaskRequest $request,
        TreatmentTask $task,
        AssignTreatmentTask $assign,
    ): RedirectResponse {
        try {
            $assign->assign($task, $request->user(), (int) $request->validated('assignee_user_id'));
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (UnqualifiedAssigneeException|InvalidTreatmentTransitionException $exception) {
            return back()->withErrors(['assignee_user_id' => $exception->getMessage()]);
        }

        return back()->with('status', 'وظیفه تخصیص داده شد.');
    }

    public function updateStatus(
        UpdateTreatmentTaskStatusRequest $request,
        TreatmentTask $task,
        UpdateTreatmentTaskStatus $update,
    ): RedirectResponse {
        $status = TreatmentTaskStatus::from($request->validated('status'));

        try {
            $update->handle(
                $task,
                $request->user(),
                $status,
                $request->validated('completion_note') ?? null,
            );
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidTreatmentTransitionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('status', 'وضعیت وظیفه به‌روزرسانی شد.');
    }
}
