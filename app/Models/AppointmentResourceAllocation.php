<?php

namespace App\Models;

use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'appointment_id',
    'resource_type',
    'resource_id',
    'starts_at',
    'ends_at',
    'is_required',
])]
class AppointmentResourceAllocation extends Model
{
    protected function casts(): array
    {
        return [
            'resource_type' => ResourceType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_required' => 'boolean',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
