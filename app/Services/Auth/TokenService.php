<?php

namespace App\Services\Auth;

use App\Dtos\TokenPair;
use App\Exceptions\RefreshTokenInvalidException;
use App\Models\User;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class TokenService
{
    public const int ACCESS_TTL_MINUTES = 30;

    public const int REFRESH_TTL_DAYS = 30;

    public function __construct(private RefreshTokenRepositoryInterface $refreshTokens) {}

    public function issue(User $user): TokenPair
    {
        $expiresAt = now()->addMinutes(self::ACCESS_TTL_MINUTES);

        $accessToken = $user->createToken('api', ['*'], $expiresAt);
        $refreshToken = Str::random(64);
        $refreshExpiresAt = now()->addDays(self::REFRESH_TTL_DAYS);

        $this->refreshTokens->create(
            $user->id,
            $accessToken->accessToken->id,
            hash('sha256', $refreshToken),
            $refreshExpiresAt,
        );

        return new TokenPair(
            $user,
            $accessToken->plainTextToken,
            CarbonImmutable::instance($expiresAt),
            $refreshToken,
            CarbonImmutable::instance($refreshExpiresAt),
        );
    }

    public function rotate(string $plainRefreshToken): TokenPair
    {
        $hash = hash('sha256', $plainRefreshToken);
        $token = $this->refreshTokens->findByTokenHash($hash);

        if ($token === null) {
            throw new RefreshTokenInvalidException;
        }

        if ($token->revoked_at !== null) {
            $this->refreshTokens->revokeAllForUser($token->user_id);

            throw new RefreshTokenInvalidException;
        }

        if ($token->expires_at->isPast()) {
            throw new RefreshTokenInvalidException;
        }

        $this->refreshTokens->revoke($token);
        $this->refreshTokens->deleteAccessToken($token->access_token_id);

        return $this->issue($token->user);
    }

    public function logout(int $accessTokenId): void
    {
        $refreshToken = $this->refreshTokens->findActiveByAccessTokenId($accessTokenId);

        if ($refreshToken !== null) {
            $this->refreshTokens->revoke($refreshToken);
        }

        $this->refreshTokens->deleteAccessToken($accessTokenId);
    }
}
