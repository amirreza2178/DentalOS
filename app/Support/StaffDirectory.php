<?php

namespace App\Support;

use App\Enums\UserRole;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class StaffDirectory
{
    public static function role(string $directory): UserRole
    {
        return match ($directory) {
            'specialists' => UserRole::Specialist,
            'clinical-staff' => UserRole::ClinicalStaff,
            'receptionists' => UserRole::Receptionist,
            default => throw new NotFoundHttpException(),
        };
    }

    public static function label(string $directory): string
    {
        return match ($directory) {
            'specialists' => 'متخصصان',
            'clinical-staff' => 'کادر بالینی',
            'receptionists' => 'پذیرش',
            default => throw new NotFoundHttpException(),
        };
    }
}
