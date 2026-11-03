<?php

namespace App\Support;

final class PhoneNormalizer
{
    /**
     * Canonical local mobile form used only for duplicate assistance and search.
     * An empty result means the value is not a usable phone, not that it is invalid.
     */
    public function normalize(?string $phone): ?string
    {
        $ascii = Digits::toAscii($phone);
        $digits = preg_replace('/\D+/', '', $ascii) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        }

        if (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
