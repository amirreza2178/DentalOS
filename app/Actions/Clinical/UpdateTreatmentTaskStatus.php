<?php

namespace App\Actions\Clinical;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\OperationalEventType;
use App\Enums\TreatmentTaskStatus;
use App\Exceptions\Clinical\InvalidTreatmentTransitionException;
use App\Models\TreatmentTask;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateTreatmentTaskStatus
{
    public function __construct(private RecordOperationalEvent $events) {}

    public function handle(
        TreatmentTask $task,
        User $actor,
        TreatmentTaskStatus $next,
        ?string $completionNote = null,
    ): TreatmentTask {
        if ($next === TreatmentTaskStatus::Cancelled) {
            if (! Gate::forUser($actor)->allows('cancel', $task)) {
                throw new AuthorizationException('لغو وظیفه مجاز نیست.');
            }
        } elseif ($next === TreatmentTaskStatus::Completed) {
            if (! Gate::forUser($actor)->allows('complete', $task)) {
                throw new AuthorizationException('تکمیل وظیفه مجاز نیست.');
            }
        } elseif (! Gate::forUser($actor)->allows('updateStatus', $task)) {
            throw new AuthorizationException('تغییر وضعیت وظیفه مجاز نیست.');
        }

        if ($next === TreatmentTaskStatus::Completed && ! filled(trim((string) $completionNote))) {
            throw new InvalidTreatmentTransitionException('برای تکمیل وظیفه، یادداشت نتیجه الزامی است.');
        }

        return DB::transaction(function () use ($task, $actor, $next, $completionNote): TreatmentTask {
            $task = TreatmentTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($task->status->isTerminal()) {
                throw new InvalidTreatmentTransitionException('وظیفه پایان‌یافته بدون مجوز دوباره فعال نمی‌شود.');
            }

            if (! $task->status->canTransitionTo($next)) {
                throw new InvalidTreatmentTransitionException('انتقال وضعیت وظیفه مجاز نیست.');
            }

            $from = $task->status->value;

            $task->status = $next;

            if ($next === TreatmentTaskStatus::Completed) {
                $task->completion_note = trim((string) $completionNote);
                $task->completed_at = now();
            }

            if ($next === TreatmentTaskStatus::Cancelled) {
                $task->cancelled_at = now();
                if (filled($completionNote)) {
                    $task->completion_note = trim((string) $completionNote);
                }
            }

            $task->save();

            $this->events->record(
                OperationalEventType::TreatmentTaskStatusChanged,
                $task,
                $actor,
                $from,
                $task->status->value,
            );

            return $task->refresh();
        });
    }
}
