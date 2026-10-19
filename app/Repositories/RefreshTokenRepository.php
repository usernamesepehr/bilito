<?php

namespace App\Repositories;

use App\DataTransferObjects\RefreshTokenData;
use App\Models\RefreshToken;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public function findByPlainToken(string $plain): ?RefreshToken
    {
        return RefreshToken::query()->where('token_hash', self::hash($plain))->first();
    }

    public function findByAccessTokenId(int $accessTokenId): ?RefreshToken
    {
        return RefreshToken::query()->where('access_token_id', $accessTokenId)->first();
    }

    public function create(RefreshTokenData $data): RefreshToken
    {
        return RefreshToken::query()->create([
            'chain_id' => $data->chainId,
            'user_id' => $data->userId,
            'access_token_id' => $data->accessTokenId,
            'token_hash' => self::hash($data->plainToken),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function revoke(RefreshToken $token): void
    {
        $token->revoked_at = now();
        $token->save();
    }

    public function revokeChain(string $chainId): void
    {
        RefreshToken::query()
            ->where('chain_id', $chainId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeForAccess(int $accessTokenId): void
    {
        RefreshToken::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
