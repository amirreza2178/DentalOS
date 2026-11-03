<?php

namespace App\Enums;

enum PatientIdentityStatus: string
{
    case Verified = 'verified';
    case UnverifiedOnline = 'unverified_online';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'هویت تأییدشده',
            self::UnverifiedOnline => 'درخواست آنلاین تأییدنشده',
        };
    }
}
