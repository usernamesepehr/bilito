<?php

namespace App\DataTransferObjects;

class RefreshTokenData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $chainId,
        public readonly int $accessTokenId,
        public readonly string $plainToken,
    ) {}
}
