<?php

namespace App\Repositories\Contracts;

use App\DataTransferObjects\RefreshTokenData;
use App\Models\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function findByPlainToken(string $plain): ?RefreshToken;

    public function findByAccessTokenId(int $accessTokenId): ?RefreshToken;

    public function create(RefreshTokenData $data): RefreshToken;

    public function revoke(RefreshToken $token): void;

    public function revokeChain(string $chainId): void;

    public function revokeForAccess(int $accessTokenId): void;
}
