<?php

namespace App\Models;

use App\Enums\Qualification;
use App\Enums\StaffType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'staff_type',
    'qualification',
    'title',
    'professional_summary',
    'is_active',
    'can_take_clinical_notes',
])]
class StaffProfile extends Model
{
    protected function casts(): array
    {
        return [
            'staff_type' => StaffType::class,
            'qualification' => Qualification::class,
            'is_active' => 'boolean',
            'can_take_clinical_notes' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
