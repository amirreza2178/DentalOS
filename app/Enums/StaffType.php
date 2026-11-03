<?php

namespace App\Enums;

enum StaffType: string
{
    case Specialist = 'specialist';
    case ClinicalStaff = 'clinical_staff';

    public function label(): string
    {
        return match ($this) {
            self::Specialist => 'متخصص',
            self::ClinicalStaff => 'کادر بالینی',
        };
    }
}
