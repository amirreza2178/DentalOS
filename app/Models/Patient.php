<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\PatientIdentityStatus;
use App\Enums\PatientStatus;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'code',
    'first_name',
    'last_name',
    'national_id',
    'mobile',
    'mobile_normalized',
    'alt_phone',
    'birth_date',
    'gender',
    'status',
    'identity_status',
    'notes_admin',
])]
class Patient extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'status' => PatientStatus::class,
            'identity_status' => PatientIdentityStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PatientVisit::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function scopeMatchingSearch(Builder $query, string $term): Builder
    {
        $normalized = app(PhoneNormalizer::class)->normalize($term);
        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like, $normalized): void {
            $query->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('national_id', 'like', $like)
                ->orWhere('mobile', 'like', $like)
                ->orWhere('alt_phone', 'like', $like);

            if ($normalized !== null) {
                $query->orWhere('mobile_normalized', $normalized);
            }
        });
    }
}
