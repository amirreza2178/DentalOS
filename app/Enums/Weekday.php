<?php

namespace App\Enums;

enum Weekday: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function label(): string
    {
        return match ($this) {
            self::Saturday => 'شنبه',
            self::Sunday => 'یکشنبه',
            self::Monday => 'دوشنبه',
            self::Tuesday => 'سه‌شنبه',
            self::Wednesday => 'چهارشنبه',
            self::Thursday => 'پنجشنبه',
            self::Friday => 'جمعه',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $day) {
            $options[$day->value] = $day->label();
        }

        return $options;
    }
}
