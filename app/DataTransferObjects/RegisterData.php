<?php

namespace App\DataTransferObjects;

class RegisterData
{
    public function __construct(
        public readonly string $phone,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $gender,
        public readonly string $nationalId,
        public readonly string $password,
    ) {}
}
