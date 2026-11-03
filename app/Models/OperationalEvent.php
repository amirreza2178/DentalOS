<?php

namespace App\Models;

use App\Enums\OperationalEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'event_type',
    'actor_user_id',
    'subject_type',
    'subject_id',
    'from_status',
    'to_status',
])]
class OperationalEvent extends Model
{
    protected function casts(): array
    {
        return [
            'event_type' => OperationalEventType::class,
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
