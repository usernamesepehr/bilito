<?php

namespace App\Dtos;

readonly class RegisterData
{
    public function __construct(
        public string $phone,
        public string $password,
        public string $firstname,
        public string $lastname,
        public string $gender,
        public string $nationalId,
    ) {}
}
