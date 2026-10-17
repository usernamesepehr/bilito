<?php

namespace App\Dtos;

use App\Models\User;
use Carbon\CarbonImmutable;

readonly class TokenPair
{
    public function __construct(
        public User $user,
        public string $accessToken,
        public CarbonImmutable $accessTokenExpiresAt,
        public string $refreshToken,
        public CarbonImmutable $refreshTokenExpiresAt,
        public string $tokenType = 'Bearer',
    ) {}
}
