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

        return DB::transaction(function () use ($followUp, $actor, $outcomeNote): FollowUp {
            $locked = FollowUp::query()->whereKey($followUp->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === FollowUpStatus::OfferedToPatient) {
                return $locked->refresh();
            }

            if ($locked->status !== FollowUpStatus::Proposed) {
                throw new InvalidTreatmentTransitionException('این پیگیری دیگر قابل علامت‌گذاری به‌عنوان پیشنهادی به بیمار نیست.');
            }

            $from = $locked->status->value;
            $locked->status = FollowUpStatus::OfferedToPatient;

            if (filled($outcomeNote)) {
                $locked->outcome_note = trim($outcomeNote);
            }

            $locked->save();

            $this->events->record(
                OperationalEventType::FollowUpStatusChanged,
                $locked,
                $actor,
                $from,
                $locked->status->value,
            );

            return $locked->refresh();
        }, 3);
    }
}
