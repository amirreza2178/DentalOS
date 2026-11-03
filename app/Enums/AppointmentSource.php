<?php

namespace App\Enums;

enum AppointmentSource: string
{
    case Online = 'online';
    case Receptionist = 'receptionist';
    case SystemSuggested = 'system_suggested';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'آنلاین',
            self::Receptionist => 'پذیرش',
            self::SystemSuggested => 'پیشنهاد سیستم',
        };
    }
}
