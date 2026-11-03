<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'work_shift_id',
    'schedule_date',
    'starts_at',
    'ends_at',
    'is_unavailable',
    'is_active',
])]
class StaffSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
            'is_unavailable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }
}
