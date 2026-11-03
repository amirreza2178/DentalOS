<?php

namespace App\Models;

use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'weekday', 'starts_at', 'ends_at', 'is_active'])]
class WorkShift extends Model
{
    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
            'is_active' => 'boolean',
        ];
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(ShiftBreak::class);
    }

    public function staffSchedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }
}
