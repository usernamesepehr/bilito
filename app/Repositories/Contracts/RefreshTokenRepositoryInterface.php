<?php

namespace App\Repositories\Contracts;

use App\Models\RefreshToken;
use DateTimeInterface;

interface RefreshTokenRepositoryInterface
{
    public function create(int $userId, int $accessTokenId, string $tokenHash, DateTimeInterface $expiresAt): RefreshToken;

    public function findByTokenHash(string $tokenHash): ?RefreshToken;

    public function revoke(RefreshToken $token): void;

    public function revokeAllForUser(int $userId): void;

    public function findActiveByAccessTokenId(int $accessTokenId): ?RefreshToken;

    public function deleteAccessToken(int $accessTokenId): void;
}
