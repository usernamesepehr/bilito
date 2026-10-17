<?php

namespace Tests\Unit\Support;

use App\Support\NationalId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NationalIdTest extends TestCase
{
    public static function persian_digit_input_provider(): array
    {
        return [
            'persian digits' => ['۰۰۷۹۱۵۰۱۱۱'],
            'arabic-indic digits' => ['٠٠٧٩١٥٠١١١'],
        ];
    }

    #[DataProvider('persian_digit_input_provider')]
    public function test_it_normalizes_non_latin_digits(string $input): void
    {
        $this->assertSame('0079150111', NationalId::normalize($input));
    }

    public function test_it_accepts_a_valid_national_id(): void
    {
        $this->assertTrue(NationalId::isValid('0079150111'));
    }

    public function test_it_accepts_a_valid_persian_digit_national_id(): void
    {
        $this->assertTrue(NationalId::isValid(NationalId::normalize('۰۰۷۹۱۵۰۱۱۱')));
    }

    public static function invalid_checksum_provider(): array
    {
        return [
            'wrong check digit from known-good id' => ['0079150112'],
            'invalid checksum' => ['0680123456'],
        ];
    }

    #[DataProvider('invalid_checksum_provider')]
    public function test_it_rejects_a_wrong_checksum(string $input): void
    {
        $this->assertFalse(NationalId::isValid($input));
    }

    public function test_it_rejects_all_repeated_digits_even_when_the_checksum_passes(): void
    {
        $this->assertFalse(NationalId::isValid('1111111111'));
    }

    public function test_it_rejects_an_id_that_is_too_short(): void
    {
        $this->assertFalse(NationalId::isValid('12345'));
    }
}
