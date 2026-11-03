<?php

namespace App\Actions\Reception;

use App\Enums\OperationalEventType;
use App\Enums\QueueStatus;
use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Models\PatientVisit;
use App\Models\User;
use App\Models\WaitingQueueEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CallNextInQueue
{
    public function __construct(private readonly RecordOperationalEvent $events) {}

    public function handle(string $queueDate, User $actor): WaitingQueueEntry
    {
        if (! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند بیمار بعدی را فراخوان کند.');
        }

        return DB::transaction(function () use ($queueDate, $actor): WaitingQueueEntry {
            $entries = WaitingQueueEntry::query()
                ->whereDate('queue_date', $queueDate)
                ->whereIn('status', QueueStatus::activeValues())
                ->orderBy('position')
                ->lockForUpdate()
                ->get();

            if ($entries->contains(fn (WaitingQueueEntry $entry) => $entry->status === QueueStatus::Called)) {
                throw new ReceptionConflictException('یک بیمار فراخوانده‌شده هنوز در صف است. اول خدمت را شروع کنید یا او را از صف خارج کنید.');
            }

            $next = $entries->first(fn (WaitingQueueEntry $entry) => $entry->status === QueueStatus::Waiting);

            if ($next === null) {
                throw new ReceptionConflictException('بیماری در صف انتظار نیست.');
            }

            $visit = PatientVisit::query()->whereKey($next->patient_visit_id)->lockForUpdate()->firstOrFail();

            if (! $visit->status->canTransitionTo(VisitStatus::Called)) {
                throw new ReceptionConflictException('وضعیت ویزیت برای فراخوان مجاز نیست.');
            }

            $fromQueue = $next->status->value;
            $fromVisit = $visit->status->value;
            $now = now();

            $next->status = QueueStatus::Called;
            $next->called_at = $now;
            $next->called_by_user_id = $actor->id;
            $next->save();

            $visit->status = VisitStatus::Called;
            $visit->called_at = $now;
            $visit->save();

            $this->events->record(OperationalEventType::PatientCalled, $next, $actor, $fromQueue, QueueStatus::Called->value);
            $this->events->record(OperationalEventType::PatientCalled, $visit, $actor, $fromVisit, VisitStatus::Called->value);

            return $next->load(['visit.patient', 'visit.appointment.treatmentService']);
        });
    }
}
