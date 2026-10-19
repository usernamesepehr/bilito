<?php

namespace App\Services;

use App\DataTransferObjects\RefreshTokenData;
use App\DataTransferObjects\TokenPair;
use App\Exceptions\RefreshTokenInvalidException;
use App\Models\User;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;
use Illuminate\Support\Str;

class TokenService
{
    public const REFRESH_TTL_DAYS = 30;

    public function __construct(private readonly RefreshTokenRepositoryInterface $refreshTokens) {}

    public function issue(User $user, ?string $chainId = null): TokenPair
    {
        $accessTokenExpiresAt = now()->addMinutes(30);
        $refreshTokenExpiresAt = now()->addDays(self::REFRESH_TTL_DAYS);

        $pair = $user->createToken(name: 'auth', expiresAt: $accessTokenExpiresAt);
        $plainRefresh = Str::random(64);

        $this->refreshTokens->create(new RefreshTokenData(
            userId: $user->getKey(),
            chainId: $chainId ?? (string) Str::uuid(),
            accessTokenId: $pair->accessToken->id,
            plainToken: $plainRefresh,
        ));

        return new TokenPair(
            accessToken: $pair->plainTextToken,
            refreshToken: $plainRefresh,
            accessTokenExpiresAt: $accessTokenExpiresAt,
            refreshTokenExpiresAt: $refreshTokenExpiresAt,
            user: $user,
        );
    }

    public function rotate(string $plainRefreshToken): TokenPair
    {
        $token = $this->refreshTokens->findByPlainToken($plainRefreshToken);

        if (! $token) {
            throw new RefreshTokenInvalidException;
        }

        if ($token->revoked_at !== null) {
            $this->refreshTokens->revokeChain($token->chain_id);

            throw new RefreshTokenInvalidException;
        }

        if ($token->expires_at->isPast()) {
            throw new RefreshTokenInvalidException;
        }

        $user = $token->user;
        $this->refreshTokens->revoke($token);
        $token->accessToken()->first()?->delete();
        $token->access_token_id = null;
        $token->save();

        return $this->issue($user, $token->chain_id);
    }

    public function revokeByRefresh(string $plainRefreshToken): void
    {
        $token = $this->refreshTokens->findByPlainToken($plainRefreshToken);

        if ($token) {
            $this->refreshTokens->revoke($token);
        }
    }

    public function revokeForAccess(int $accessTokenId): void
    {
        $this->refreshTokens->revokeForAccess($accessTokenId);
    }
}
