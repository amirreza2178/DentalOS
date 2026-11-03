<?php

namespace App\Support;

final class ClockInput
{
    public static function normalize(?string $value): ?string
    {
        $value = trim(Digits::toAscii($value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $value, $matches) !== 1) {
            return $value;
        }

        return $matches[1].':'.$matches[2].':00';
    }

    public static function short(?string $value): string
    {
        $normalized = self::normalize($value);

        return $normalized === null ? '' : substr($normalized, 0, 5);
    }
}
