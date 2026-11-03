<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Receptionist = 'receptionist';
    case Specialist = 'specialist';
    case ClinicalStaff = 'clinical_staff';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'مدیر',
            self::Receptionist => 'پذیرش',
            self::Specialist => 'متخصص',
            self::ClinicalStaff => 'کادر بالینی',
            self::Patient => 'بیمار',
        };
    }

    public function requiresStaffProfile(): bool
    {
        return $this === self::Specialist || $this === self::ClinicalStaff;
    }
}
