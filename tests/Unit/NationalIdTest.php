<?php

namespace Tests\Unit;

use App\Support\NationalId;
use PHPUnit\Framework\TestCase;

class NationalIdTest extends TestCase
{
    public function test_accepts_valid_code(): void
    {
        $this->assertTrue(NationalId::validate('0468534474'));
    }

    public function test_accepts_valid_code_as_string_of_ten(): void
    {
        $this->assertTrue(NationalId::validate('0079926411'));
    }

    public function test_rejects_invalid_checksum(): void
    {
        $this->assertFalse(NationalId::validate('0468534479'));
        $this->assertFalse(NationalId::validate('04685344739'));
    }

    public function test_rejects_repeated_digits(): void
    {
        $this->assertFalse(NationalId::validate('1111111111'));
        $this->assertFalse(NationalId::validate('0000000000'));
    }

    public function test_rejects_wrong_length(): void
    {
        $this->assertFalse(NationalId::validate('046853447'));
        $this->assertFalse(NationalId::validate('04685344791'));
    }

    public function test_rejects_non_digits(): void
    {
        $this->assertFalse(NationalId::validate('046853447a'));
        $this->assertFalse(NationalId::validate(''));
        $this->assertFalse(NationalId::validate(null));
    }
}
