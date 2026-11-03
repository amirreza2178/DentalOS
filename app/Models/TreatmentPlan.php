<?php

namespace App\Models;

use App\Enums\TreatmentPlanStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id',
    'patient_visit_id',
    'created_by_user_id',
    'status',
    'clinical_summary',
    'operational_summary',
])]
class TreatmentPlan extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TreatmentPlanStatus::class,
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TreatmentTask::class);
    }
}
