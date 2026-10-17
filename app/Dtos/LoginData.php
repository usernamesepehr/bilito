<?php

namespace App\Dtos;

readonly class LoginData
{
    public function __construct(
        public string $phone,
        public string $password,
    ) {}
}
