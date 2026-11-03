<?php

namespace App\Support;

final class CapabilityList
{
    /**
     * @return list<string>|null
     */
    public static function parse(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }

        $parts = preg_split('/[,،]/u', Digits::toAscii($value)) ?: [];
        $parts = array_values(array_unique(array_filter(
            array_map(static fn (string $part): string => trim($part), $parts),
            static fn (string $part): bool => $part !== '',
        )));

        return $parts === [] ? null : $parts;
    }
}
