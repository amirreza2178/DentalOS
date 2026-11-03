<?php

namespace App\Enums;

enum TreatmentPlanStatus: string
{
    case Proposed = 'proposed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'پیشنهادی',
            self::Active => 'فعال',
            self::Completed => 'تکمیل‌شده',
            self::Cancelled => 'لغو‌شده',
        };
    }

    public function canBeEdited(): bool
    {
        return in_array($this, [self::Proposed, self::Active], true);
    }
}
