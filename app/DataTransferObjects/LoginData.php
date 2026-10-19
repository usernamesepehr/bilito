<?php

namespace App\DataTransferObjects;

class LoginData
{
    public function __construct(
        public readonly string $phone,
        public readonly string $password,
    ) {}
}
