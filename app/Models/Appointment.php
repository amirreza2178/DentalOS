<?php

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'patient_id',
    'treatment_service_id',
    'starts_at',
    'ends_at',
    'status',
    'source',
    'confirmation_mode',
    'booked_by_user_id',
    'approved_by_user_id',
    'rescheduled_from_id',
    'follow_up_id',
    'patient_notes',
    'public_reference',
    'decision_reason',
    'decided_at',
])]
class Appointment extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'decided_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'source' => AppointmentSource::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentService(): BelongsTo
    {
        return $this->belongsTo(TreatmentService::class);
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function rescheduledTo(): HasOne
    {
        return $this->hasOne(self::class, 'rescheduled_from_id');
    }

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(FollowUp::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AppointmentResourceAllocation::class);
    }

    public function visit(): HasOne
    {
        return $this->hasOne(PatientVisit::class);
    }
}
