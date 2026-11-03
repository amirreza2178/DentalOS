<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case Proposed = 'proposed';
    case OfferedToPatient = 'offered_to_patient';
    case Accepted = 'accepted';
    case Scheduled = 'scheduled';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'پیشنهادی',
            self::OfferedToPatient => 'پیشنهاد به بیمار',
            self::Accepted => 'پذیرفته‌شده',
            self::Scheduled => 'زمان‌بندی‌شده',
            self::Declined => 'رد‌شده',
            self::Expired => 'منقضی',
            self::Cancelled => 'لغو‌شده',
        };
    }

    public function canBeScheduled(): bool
    {
        return in_array($this, [
            self::Proposed,
            self::OfferedToPatient,
            self::Accepted,
        ], true);
    }
}
