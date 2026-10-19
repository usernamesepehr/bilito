<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_normalizes_plain_phone(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('09123456789'));
    }

    public function test_normalizes_persian_digits(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('۰۹۱۲۳۴۵۶۷۸۹'));
    }

    public function test_normalizes_arabic_indic_digits(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('٠٩١٢٣٤٥٦٧٨٩'));
    }

    public function test_strips_plus98_prefix(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('+989123456789'));
    }

    public function test_strips_0098_prefix(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('00989123456789'));
    }

    public function test_strips_98_prefix(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('989123456789'));
    }

    public function test_prefixes_leading_nine(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('9123456789'));
    }

    public function test_strips_separators(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('0912 345 6789'));
    }

    public function test_normalizes_with_persian_digits_and_prefix(): void
    {
        $this->assertSame('09123456789', PhoneNormalizer::normalize('+۹۸۹۱۲۳۴۵۶۷۸۹'));
    }

    public function test_returns_null_for_too_short(): void
    {
        $this->assertNull(PhoneNormalizer::normalize('123'));
    }

    public function test_returns_null_for_letters(): void
    {
        $this->assertNull(PhoneNormalizer::normalize('091234567abc'));
    }

    public function test_returns_null_for_empty(): void
    {
        $this->assertNull(PhoneNormalizer::normalize(''));
        $this->assertNull(PhoneNormalizer::normalize(null));
    }
}
