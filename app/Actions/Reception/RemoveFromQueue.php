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

class RemoveFromQueue
{
    public function __construct(private readonly RecordOperationalEvent $events) {}

    public function handle(WaitingQueueEntry $entry, User $actor): WaitingQueueEntry
    {
        if (! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند بیمار را از صف خارج کند.');
        }

        return DB::transaction(function () use ($entry, $actor): WaitingQueueEntry {
            $entry = WaitingQueueEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if (! $entry->status->isActive()) {
                throw new ReceptionConflictException('این ورودی دیگر در صف فعال نیست.');
            }

            if ($entry->status === QueueStatus::InService) {
                throw new ReceptionConflictException('بیمار در حال خدمت است. اول ویزیت را تمام کنید.');
            }

            $visit = PatientVisit::query()->whereKey($entry->patient_visit_id)->lockForUpdate()->firstOrFail();
            $fromQueue = $entry->status->value;
            $fromVisit = $visit->status->value;
            $now = now();

            $entry->status = QueueStatus::Removed;
            $entry->completed_at = $now;
            $entry->save();

            // Appointment stays checked_in — historical check-in must remain true.
            if ($visit->status->isActive() && $visit->status->canTransitionTo(VisitStatus::Cancelled)) {
                $visit->status = VisitStatus::Cancelled;
                $visit->completed_at = $now;
                $visit->save();
                $this->events->record(OperationalEventType::VisitCancelled, $visit, $actor, $fromVisit, VisitStatus::Cancelled->value);
            }

            $this->events->record(OperationalEventType::QueueEntryRemoved, $entry, $actor, $fromQueue, QueueStatus::Removed->value);

            return $entry->fresh(['visit.patient', 'visit.appointment']);
        });
    }
}
