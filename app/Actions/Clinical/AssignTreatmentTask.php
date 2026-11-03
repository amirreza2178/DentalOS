<?php

namespace App\Actions\Clinical;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\OperationalEventType;
use App\Enums\Qualification;
use App\Enums\StaffType;
use App\Enums\TreatmentTaskStatus;
use App\Enums\UserRole;
use App\Exceptions\Clinical\InvalidTreatmentTransitionException;
use App\Exceptions\Clinical\UnqualifiedAssigneeException;
use App\Models\TreatmentPlan;
use App\Models\TreatmentTask;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssignTreatmentTask
{
    public function __construct(private RecordOperationalEvent $events) {}

    /**
     * @param  array{title: string, instructions?: ?string, required_qualification: string, assignee_user_id?: ?int}  $data
     */
    public function create(TreatmentPlan $plan, User $actor, array $data): TreatmentTask
    {
        if (! Gate::forUser($actor)->allows('create', TreatmentTask::class)) {
            throw new AuthorizationException('ثبت وظیفه درمانی مجاز نیست.');
        }

        if (! Gate::forUser($actor)->allows('update', $plan)) {
            throw new AuthorizationException('برنامه درمانی قابل تغییر نیست.');
        }

        $qualification = Qualification::from($data['required_qualification']);
        $assigneeId = isset($data['assignee_user_id']) && (int) $data['assignee_user_id'] > 0
            ? (int) $data['assignee_user_id']
            : null;
        $assignee = $assigneeId === null ? null : $this->qualifiedAssignee($assigneeId, $qualification);

        return DB::transaction(function () use ($plan, $actor, $data, $qualification, $assignee): TreatmentTask {
            $status = $assignee === null ? TreatmentTaskStatus::Pending : TreatmentTaskStatus::Assigned;

            $task = TreatmentTask::query()->create([
                'treatment_plan_id' => $plan->id,
                'title' => trim($data['title']),
                'instructions' => filled($data['instructions'] ?? null) ? trim((string) $data['instructions']) : null,
                'required_qualification' => $qualification,
                'assignee_user_id' => $assignee?->id,
                'assigned_by_user_id' => $assignee === null ? null : $actor->id,
                'status' => $status,
            ]);

            $this->events->record(
                $assignee === null
                    ? OperationalEventType::TreatmentTaskCreated
                    : OperationalEventType::TreatmentTaskAssigned,
                $task,
                $actor,
                null,
                $task->status->value,
            );

            return $task;
        });
    }

    public function assign(TreatmentTask $task, User $actor, int $assigneeUserId): TreatmentTask
    {
        if (! Gate::forUser($actor)->allows('assign', $task)) {
            throw new AuthorizationException('تخصیص وظیفه مجاز نیست.');
        }

        if ($task->status->isTerminal()) {
            throw new InvalidTreatmentTransitionException('وظیفه تکمیل‌شده یا لغو‌شده قابل تخصیص مجدد نیست.');
        }

        if (! in_array($task->status, [TreatmentTaskStatus::Pending, TreatmentTaskStatus::Assigned], true)) {
            throw new InvalidTreatmentTransitionException('فقط وظایف در انتظار یا تخصیص‌یافته قابل تغییر مسئول هستند.');
        }

        $assignee = $this->qualifiedAssignee($assigneeUserId, $task->required_qualification);

        return DB::transaction(function () use ($task, $actor, $assignee): TreatmentTask {
            $from = $task->status->value;
            $task->fill([
                'assignee_user_id' => $assignee->id,
                'assigned_by_user_id' => $actor->id,
                'status' => TreatmentTaskStatus::Assigned,
            ]);
            $task->save();

            $this->events->record(
                OperationalEventType::TreatmentTaskAssigned,
                $task,
                $actor,
                $from,
                $task->status->value,
            );

            return $task->refresh();
        });
    }

    private function qualifiedAssignee(int $assigneeUserId, Qualification $required): User
    {
        $assignee = User::query()
            ->with('staffProfile')
            ->whereKey($assigneeUserId)
            ->first();

        if ($assignee === null || ! $assignee->is_active || $assignee->role !== UserRole::ClinicalStaff) {
            throw new UnqualifiedAssigneeException('مسئول باید نیروی درمانی فعال باشد.');
        }

        $profile = $assignee->staffProfile;

        if ($profile === null
            || ! $profile->is_active
            || $profile->staff_type !== StaffType::ClinicalStaff
            || $profile->qualification !== $required
        ) {
            throw new UnqualifiedAssigneeException('صلاحیت ثبت‌شده این نیرو با نیاز وظیفه هم‌خوان نیست.');
        }

        return $assignee;
    }
}
