<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['work_shift_id', 'name', 'starts_at', 'ends_at'])]
class ShiftBreak extends Model
{
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }
}
