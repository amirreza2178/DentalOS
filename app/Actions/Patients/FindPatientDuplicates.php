<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;

class FindPatientDuplicates
{
    /**
     * Other records that share a normalized mobile. Records are never merged.
     *
     * @return Collection<int, Patient>
     */
    public function sharingMobile(Patient $patient): Collection
    {
        if (blank($patient->mobile_normalized)) {
            return new Collection;
        }

        return Patient::query()
            ->where('mobile_normalized', $patient->mobile_normalized)
            ->whereKeyNot($patient->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
