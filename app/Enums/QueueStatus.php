<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case InService = 'in_service';
    case Completed = 'completed';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'در صف',
            self::Called => 'فراخوانده‌شده',
            self::InService => 'در خدمت',
            self::Completed => 'خارج‌شده',
            self::Removed => 'حذف از صف',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Waiting, self::Called, self::InService], true);
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return array_map(
            fn (self $status): string => $status->value,
            [self::Waiting, self::Called, self::InService],
        );
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
            self::Waiting => [self::Called, self::InService, self::Removed],
            self::Called => [self::InService, self::Removed],
            self::InService => [self::Completed, self::Removed],
            self::Completed, self::Removed => [],
        };
    }
}
