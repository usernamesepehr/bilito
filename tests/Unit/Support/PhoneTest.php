<?php

namespace Tests\Unit\Support;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_it_returns_a_canonical_number_unchanged(): void
    {
        $this->assertSame('09123456789', Phone::normalize('09123456789'));
    }

    public static function normalizable_input_provider(): array
    {
        return [
            '+98 country code' => ['+989123456789'],
            '0098 country code' => ['00989123456789'],
            'missing leading zero' => ['9123456789'],
            'persian digits' => ['۰۹۱۲۳۴۵۶۷۸۹'],
            'persian digits with persian 98 prefix' => ['+۹۸۹۱۲۳۴۵۶۷۸۹'],
            'arabic-indic digits' => ['٠٩١٢٣٤٥٦٧٨٩'],
        ];
    }

    #[DataProvider('normalizable_input_provider')]
    public function test_it_normalizes_input_to_the_canonical_zero_prefixed_number(string $input): void
    {
        $this->assertSame('09123456789', Phone::normalize($input));
    }
}
