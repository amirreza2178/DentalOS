<?php

namespace App\Enums;

enum BookingConfirmationMode: string
{
    case Immediate = 'immediate';
    case ReceptionistApproval = 'receptionist_approval';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'تأیید فوری',
            self::ReceptionistApproval => 'نیاز به تأیید پذیرش',
        };
    }
}
