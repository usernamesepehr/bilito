<?php

namespace App\Support;

class NationalId
{
    public static function validate(?string $raw): bool
    {
        if ($raw === null || preg_match('/^\d{10}$/', $raw) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{9}$/', $raw) === 1) {
            return false;
        }

        $digits = str_split($raw);
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $digits[$i] * (10 - $i);
        }

        $remainder = $sum % 11;
        $check = (int) $digits[9];

        return $remainder < 2 ? $check === $remainder : $check === 11 - $remainder;
    }

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = PhoneNormalizer::toLatinDigits($raw);

        return preg_match('/^\d{10}$/', $digits) === 1 ? $digits : null;
    }
}
