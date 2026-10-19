<?php

namespace App\DataTransferObjects;

use Carbon\CarbonInterface;

class TokenPair
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly CarbonInterface $accessTokenExpiresAt,
        public readonly CarbonInterface $refreshTokenExpiresAt,
    ) {}
}
