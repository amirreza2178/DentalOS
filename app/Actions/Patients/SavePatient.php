<?php

namespace App\Actions\Patients;

use App\Enums\PatientIdentityStatus;
use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SavePatient
{
    public function __construct(private PhoneNormalizer $phones) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data): Patient {
            $providedCode = filled($data['code'] ?? null) ? $data['code'] : null;
            $patient = new Patient($this->attributes($data));
            $patient->code = $providedCode ?? ('TMP-'.Str::uuid());
            $patient->save();

            if ($providedCode === null) {
                $patient->code = sprintf('P-%06d', $patient->id);
                $patient->save();
            }

            return $patient->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Patient $patient, array $data): Patient
    {
        $patient->fill($this->attributes($data, keepCode: true));

        if (filled($data['code'] ?? null)) {
            $patient->code = $data['code'];
        }

        $patient->save();

        return $patient->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, bool $keepCode = false): array
    {
        $attributes = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'national_id' => $data['national_id'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'mobile_normalized' => $this->phones->normalize($data['mobile'] ?? null),
            'alt_phone' => $data['alt_phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'status' => $data['status'] ?? PatientStatus::Active,
            'identity_status' => $data['identity_status'] ?? PatientIdentityStatus::Verified,
            'notes_admin' => $data['notes_admin'] ?? null,
        ];

        if (! $keepCode && filled($data['code'] ?? null)) {
            $attributes['code'] = $data['code'];
        }

        return $attributes;
    }
}
