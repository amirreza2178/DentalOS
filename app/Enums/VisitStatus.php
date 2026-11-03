<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'در انتظار',
            self::Called => 'فراخوانده‌شده',
            self::InProgress => 'در حال ویزیت',
            self::Completed => 'تمام‌شده',
            self::Cancelled => 'لغو‌شده',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Waiting, self::Called, self::InProgress], true);
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
            self::Waiting => [self::Called, self::InProgress, self::Cancelled],
            self::Called => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }
}
