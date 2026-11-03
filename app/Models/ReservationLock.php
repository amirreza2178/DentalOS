<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['resource_type', 'resource_id', 'lock_version'])]
class ReservationLock extends Model
{
    protected function casts(): array
    {
        return [
            'lock_version' => 'integer',
        ];
    }
}
