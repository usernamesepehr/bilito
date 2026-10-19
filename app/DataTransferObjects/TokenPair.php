<?php

namespace App\DataTransferObjects;

use App\Models\User;
use Carbon\CarbonInterface;

class TokenPair
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly CarbonInterface $accessTokenExpiresAt,
        public readonly CarbonInterface $refreshTokenExpiresAt,
        public readonly ?User $user = null,
    ) {}

    public function withUser(User $user): self
    {
        return new self(
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            accessTokenExpiresAt: $this->accessTokenExpiresAt,
            refreshTokenExpiresAt: $this->refreshTokenExpiresAt,
            user: $user,
        );
    }
}
