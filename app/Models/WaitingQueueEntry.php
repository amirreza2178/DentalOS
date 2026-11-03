<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_visit_id',
    'queue_date',
    'position',
    'status',
    'arrived_at',
    'called_at',
    'started_at',
    'completed_at',
    'called_by_user_id',
])]
class WaitingQueueEntry extends Model
{
    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'status' => QueueStatus::class,
            'arrived_at' => 'datetime',
            'called_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', QueueStatus::activeValues());
    }

    public function scopeForClinicDate(Builder $query, string $gregorianDate): Builder
    {
        return $query->whereDate('queue_date', $gregorianDate);
    }
}
