<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'patient_id',
    'appointment_id',
    'status',
    'checked_in_by_user_id',
    'arrived_at',
    'called_at',
    'started_at',
    'completed_at',
])]
class PatientVisit extends Model
{
    protected function casts(): array
    {
        return [
            'status' => VisitStatus::class,
            'arrived_at' => 'datetime',
            'called_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(WaitingQueueEntry::class);
    }

    public function latestQueueEntry(): HasOne
    {
        return $this->hasOne(WaitingQueueEntry::class)->latestOfMany();
    }

    public function activeQueueEntry(): ?WaitingQueueEntry
    {
        return $this->queueEntries()
            ->whereIn('status', QueueStatus::activeValues())
            ->orderByDesc('id')
            ->first();
    }
}
