<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
    case Rescheduled = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::PendingApproval => 'منتظر تأیید',
            self::Confirmed => 'قطعی',
            self::CheckedIn => 'پذیرش‌شده',
            self::InProgress => 'در حال انجام',
            self::Completed => 'انجام‌شده',
            self::Cancelled => 'لغو‌شده',
            self::NoShow => 'عدم حضور',
            self::Rescheduled => 'جابه‌جا‌شده',
        };
    }

    public function occupiesResources(): bool
    {
        return in_array($this, self::occupying(), true);
    }

    /**
     * @return list<self>
     */
    public static function occupying(): array
    {
        return [
            self::PendingApproval,
            self::Confirmed,
            self::CheckedIn,
            self::InProgress,
        ];
    }

    /**
     * @return list<string>
     */
    public static function occupyingValues(): array
    {
        return array_map(fn (self $status): string => $status->value, self::occupying());
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval, self::Confirmed, self::Cancelled],
            self::PendingApproval => [self::Confirmed, self::Cancelled, self::Rescheduled],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow, self::Rescheduled],
            self::CheckedIn => [self::InProgress, self::Cancelled, self::NoShow],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled, self::NoShow, self::Rescheduled => [],
        };
    }
}
