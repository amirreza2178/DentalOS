<?php

namespace App\Enums;

enum Qualification: string
{
    case Unknown = 'unknown';
    case Student = 'student';
    case QualifiedClinicalPersonnel = 'qualified_clinical_personnel';
    case LicensedDentist = 'licensed_dentist';
    case OrthodonticSpecialist = 'orthodontic_specialist';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'نامشخص',
            self::Student => 'دانشجو',
            self::QualifiedClinicalPersonnel => 'نیروی واجد شرایط بالینی',
            self::LicensedDentist => 'دندان‌پزشک دارای مجوز',
            self::OrthodonticSpecialist => 'متخصص ارتودنسی',
            self::Other => 'سایر',
        };
    }
}
