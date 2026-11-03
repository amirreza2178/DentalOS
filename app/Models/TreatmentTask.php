<?php

namespace App\Models;

use App\Enums\Qualification;
use App\Enums\TreatmentTaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'treatment_plan_id',
    'title',
    'instructions',
    'required_qualification',
    'assignee_user_id',
    'assigned_by_user_id',
    'status',
    'completion_note',
    'completed_at',
    'cancelled_at',
])]
class TreatmentTask extends Model
{
    protected function casts(): array
    {
        return [
            'required_qualification' => Qualification::class,
            'status' => TreatmentTaskStatus::class,
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
