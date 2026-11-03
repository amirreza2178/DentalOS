<?php

namespace App\Actions\Reception;

use App\Enums\OperationalEventType;
use App\Models\OperationalEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordOperationalEvent
{
    public function record(
        OperationalEventType $type,
        Model $subject,
        ?User $actor = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
    ): OperationalEvent {
        return OperationalEvent::query()->create([
            'event_type' => $type,
            'actor_user_id' => $actor?->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
        ]);
    }
}
