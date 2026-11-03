<?php

namespace App\Actions\Clinical;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\FollowUpStatus;
use App\Enums\OperationalEventType;
use App\Exceptions\Clinical\InvalidTreatmentTransitionException;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeclineFollowUp
{
    public function __construct(private RecordOperationalEvent $events) {}

    public function handle(FollowUp $followUp, User $actor, ?string $outcomeNote = null): FollowUp
    {
        if (! Gate::forUser($actor)->allows('updateOperational', $followUp)) {
            throw new AuthorizationException('رد پیگیری برای این نقش مجاز نیست.');
        }

        if (! $followUp->status->canBeScheduled() && $followUp->status !== FollowUpStatus::OfferedToPatient) {
            throw new InvalidTreatmentTransitionException('این پیگیری قابل رد نیست.');
        }

        if (in_array($followUp->status, [FollowUpStatus::Scheduled, FollowUpStatus::Declined, FollowUpStatus::Cancelled], true)) {
            throw new InvalidTreatmentTransitionException('وضعیت فعلی پیگیری قابل رد نیست.');
        }

        return DB::transaction(function () use ($followUp, $actor, $outcomeNote): FollowUp {
            $from = $followUp->status->value;
            $followUp->status = FollowUpStatus::Declined;
            $followUp->outcome_note = filled($outcomeNote) ? trim($outcomeNote) : $followUp->outcome_note;
            $followUp->save();

            $this->events->record(
                OperationalEventType::FollowUpStatusChanged,
                $followUp,
                $actor,
                $from,
                $followUp->status->value,
            );

            return $followUp->refresh();
        });
    }
}
