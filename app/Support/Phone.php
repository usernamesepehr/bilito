<?php

namespace App\Support;

final class Phone
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
        $value = strtr($value, self::DIGIT_MAP);

        if (str_starts_with($value, '+98')) {
            $value = substr($value, 3);
        } elseif (str_starts_with($value, '0098')) {
            $value = substr($value, 4);
        }

        if (strlen($value) === 10 && str_starts_with($value, '9')) {
            $value = '0'.$value;
        }

        return $value;
    }
}
