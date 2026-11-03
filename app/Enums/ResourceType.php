<?php

namespace App\Enums;

enum ResourceType: string
{
    case Chair = 'chair';
    case Specialist = 'specialist';
    case ClinicalStaff = 'clinical_staff';

    public function label(): string
    {
        return match ($this) {
            self::Chair => 'یونیت',
            self::Specialist => 'متخصص',
            self::ClinicalStaff => 'کادر بالینی',
        };
    }
}
