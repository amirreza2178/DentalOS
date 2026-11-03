<?php

namespace App\Models;

use App\Enums\BookingConfirmationMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'description',
    'default_duration_minutes',
    'buffer_minutes',
    'requires_specialist',
    'required_clinical_staff_count',
    'requires_chair',
    'required_chair_capabilities',
    'booking_confirmation_mode',
    'is_bookable_online',
    'base_price',
    'is_active',
])]
class TreatmentService extends Model
{
    protected function casts(): array
    {
        return [
            'default_duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'requires_specialist' => 'boolean',
            'required_clinical_staff_count' => 'integer',
            'requires_chair' => 'boolean',
            'required_chair_capabilities' => 'array',
            'booking_confirmation_mode' => BookingConfirmationMode::class,
            'is_bookable_online' => 'boolean',
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public function capabilityRequirements(): array
    {
        return array_values($this->required_chair_capabilities ?? []);
    }

    public function occupiedMinutes(): int
    {
        return $this->default_duration_minutes + $this->buffer_minutes;
    }
}
