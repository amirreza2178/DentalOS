<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id',
    'patient_visit_id',
    'treatment_service_id',
    'proposed_window_start',
    'proposed_window_end',
    'status',
    'operational_note',
    'outcome_note',
    'created_by',
    'patient_confirmed_at',
    'authorized_by_user_id',
    'converted_appointment_id',
])]
class FollowUp extends Model
{
    protected function casts(): array
    {
        return [
            'proposed_window_start' => 'date',
            'proposed_window_end' => 'date',
            'status' => FollowUpStatus::class,
            'patient_confirmed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function treatmentService(): BelongsTo
    {
        return $this->belongsTo(TreatmentService::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'converted_appointment_id');
    }
}
