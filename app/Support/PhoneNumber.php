<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (preg_match('/^\+?[0-9\s().-]+$/', $trimmed) !== 1) {
            return $trimmed;
        }

        $digits = preg_replace('/\D+/', '', $trimmed);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return '+'.$digits;
    }

    public static function isValid(?string $value): bool
    {
        return is_string($value)
            && preg_match('/^\+[1-9][0-9]{7,14}$/', $value) === 1;
    }
}
