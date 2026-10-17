<?php

namespace App\Repositories;

use App\Models\RefreshToken;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function create(int $userId, int $accessTokenId, string $tokenHash, DateTimeInterface $expiresAt): RefreshToken
    {
        return RefreshToken::create([
            'user_id' => $userId,
            'access_token_id' => $accessTokenId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findByTokenHash(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::where('token_hash', $tokenHash)->first();
    }

    public function revoke(RefreshToken $token): void
    {
        $token->revoked_at = now();
        $token->save();
    }

    public function revokeAllForUser(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            $accessTokenIds = RefreshToken::query()
                ->where('user_id', $userId)
                ->whereNull('revoked_at')
                ->pluck('access_token_id');

            PersonalAccessToken::query()->whereIn('id', $accessTokenIds)->delete();

            RefreshToken::query()
                ->where('user_id', $userId)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        });
    }

    public function findActiveByAccessTokenId(int $accessTokenId): ?RefreshToken
    {
        return RefreshToken::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('revoked_at')
            ->first();
    }

    public function deleteAccessToken(int $accessTokenId): void
    {
        PersonalAccessToken::query()->where('id', $accessTokenId)->delete();
    }
}
