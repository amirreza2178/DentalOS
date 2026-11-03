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

class MarkFollowUpOffered
{
    public function __construct(private RecordOperationalEvent $events) {}

    public function handle(FollowUp $followUp, User $actor, ?string $outcomeNote = null): FollowUp
    {
        if (! Gate::forUser($actor)->allows('updateOperational', $followUp)) {
            throw new AuthorizationException('به‌روزرسانی پیگیری برای این نقش مجاز نیست.');
        }

        if (! in_array($followUp->status, [FollowUpStatus::Proposed, FollowUpStatus::OfferedToPatient], true)) {
            throw new InvalidTreatmentTransitionException('این پیگیری دیگر قابل علامت‌گذاری به‌عنوان پیشنهادی به بیمار نیست.');
        }

        return DB::transaction(function () use ($followUp, $actor, $outcomeNote): FollowUp {
            $from = $followUp->status->value;
            $followUp->status = FollowUpStatus::OfferedToPatient;

            if (filled($outcomeNote)) {
                $followUp->outcome_note = trim($outcomeNote);
            }

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
