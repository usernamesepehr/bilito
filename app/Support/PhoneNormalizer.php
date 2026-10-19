<?php

namespace App\Support;

class PhoneNormalizer
{
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = self::toLatinDigits($raw);
        $digits = preg_replace('/[\s\-+]/', '', self::toLatinDigits($raw));

        if (! ctype_digit($digits)) {
            return null;
        }

        $digits = self::stripCountryPrefix($digits);

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return preg_match('/^09\d{9}$/', $digits) === 1 ? $digits : null;
    }

    public static function toLatinDigits(string $raw): string
    {
        return strtr(trim($raw), self::DIGIT_MAP);
    }

    private static function stripCountryPrefix(string $digits): string
    {
        foreach (['+98', '0098', '98'] as $prefix) {
            if (str_starts_with($digits, $prefix) && strlen($digits) >= 10) {
                $stripped = substr($digits, strlen($prefix));

                return preg_match('/^9\d{9}$/', $stripped) === 1 ? '0'.$stripped : $digits;
            }
        }

        return $digits;
    }
}
