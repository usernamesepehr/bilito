<?php

namespace App\Support;

final class NationalId
{
    private const DIGIT_MAP = [
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9',
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣' => '3',
        '٤' => '4',
        '٥' => '5',
        '٦' => '6',
        '٧' => '7',
        '٨' => '8',
        '٩' => '9',
    ];

    public static function normalize(string $value): string
    {
        return strtr($value, self::DIGIT_MAP);
    }

    public static function isValid(string $value): bool
    {
        $value = self::normalize($value);

        if (preg_match('/^\d{10}$/', $value) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{9}$/', $value) === 1) {
            return false;
        }

        $digits = array_map(intval(...), str_split($value));
        $sum = 0;

        foreach (array_slice($digits, 0, 9) as $index => $digit) {
            $sum += $digit * (10 - $index);
        }

        $remainder = $sum % 11;
        $checkDigit = $remainder < 2 ? $remainder : 11 - $remainder;

        return $digits[9] === $checkDigit;
    }
}
