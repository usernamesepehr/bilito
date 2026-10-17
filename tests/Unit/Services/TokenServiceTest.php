<?php

namespace Tests\Unit\Services;

use App\Dtos\TokenPair;
use App\Exceptions\RefreshTokenInvalidException;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TokenService::class);
    }

    public function test_issue_returns_plaintext_pair_and_persists_rows_with_correct_expiry_and_hash(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();

        $pair = $this->service->issue($user);

        $this->assertInstanceOf(TokenPair::class, $pair);
        $this->assertSame($user->id, $pair->user->id);
        $this->assertSame('Bearer', $pair->tokenType);

        $expectedAccessExpiry = now()->addMinutes(TokenService::ACCESS_TTL_MINUTES);
        $expectedRefreshExpiry = now()->addDays(TokenService::REFRESH_TTL_DAYS);

        $this->assertSame($expectedAccessExpiry->format('Y-m-d H:i:s'), $pair->accessTokenExpiresAt->format('Y-m-d H:i:s'));
        $this->assertSame($expectedRefreshExpiry->format('Y-m-d H:i:s'), $pair->refreshTokenExpiresAt->format('Y-m-d H:i:s'));

        $accessRow = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();
        $this->assertSame($accessRow->id, PersonalAccessToken::findToken($pair->accessToken)->id);
        $this->assertSame($expectedAccessExpiry->format('Y-m-d H:i:s'), $accessRow->expires_at->format('Y-m-d H:i:s'));

        $refreshRow = RefreshToken::query()->where('user_id', $user->id)->sole();
        $this->assertSame($accessRow->id, $refreshRow->access_token_id);
        $this->assertNotSame($pair->refreshToken, $refreshRow->token_hash);
        $this->assertSame(64, strlen($refreshRow->token_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $refreshRow->token_hash);
        $this->assertSame(hash('sha256', $pair->refreshToken), $refreshRow->token_hash);
        $this->assertSame($expectedRefreshExpiry->format('Y-m-d H:i:s'), $refreshRow->expires_at->format('Y-m-d H:i:s'));
        $this->assertNull($refreshRow->revoked_at);
    }

    public function test_rotate_with_valid_token_issues_new_pair_and_deletes_old_rows(): void
    {
        $user = User::factory()->create();

        $pair = $this->service->issue($user);

        $oldAccessRow = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();
        $oldRefreshRow = RefreshToken::query()
            ->where('token_hash', hash('sha256', $pair->refreshToken))
            ->sole();

        $newPair = $this->service->rotate($pair->refreshToken);

        $this->assertInstanceOf(TokenPair::class, $newPair);
        $this->assertSame($user->id, $newPair->user->id);
        $this->assertNotSame($pair->accessToken, $newPair->accessToken);
        $this->assertNotSame($pair->refreshToken, $newPair->refreshToken);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldAccessRow->id]);
        $this->assertDatabaseMissing('refresh_tokens', ['id' => $oldRefreshRow->id]);

        $newAccessRow = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();
        $this->assertNotSame($oldAccessRow->id, $newAccessRow->id);
        $this->assertDatabaseHas('refresh_tokens', [
            'access_token_id' => $newAccessRow->id,
            'token_hash' => hash('sha256', $newPair->refreshToken),
        ]);

        $this->expectException(RefreshTokenInvalidException::class);
        $this->service->rotate($pair->refreshToken);
    }

    public function test_rotate_with_revoked_token_throws_and_kills_all_user_tokens(): void
    {
        $user = User::factory()->create();

        $revokedPair = $this->service->issue($user);
        $secondPair = $this->service->issue($user);
        $thirdPair = $this->service->issue($user);

        $revokedRow = RefreshToken::query()
            ->where('token_hash', hash('sha256', $revokedPair->refreshToken))
            ->sole();
        $revokedRow->revoked_at = now();
        $revokedRow->save();

        $otherAccessIds = PersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->where('id', '!=', $revokedRow->access_token_id)
            ->pluck('id');
        $this->assertSame(2, $otherAccessIds->count());

        try {
            $this->service->rotate($revokedPair->refreshToken);
            $this->fail('Rotation with a revoked refresh token must fail.');
        } catch (RefreshTokenInvalidException $exception) {
            $this->assertSame(0, RefreshToken::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->count());
            $this->assertSame(0, PersonalAccessToken::query()
                ->whereIn('id', $otherAccessIds)
                ->count());
        }
    }

    public function test_rotate_with_unknown_token_hash_throws(): void
    {
        $this->expectException(RefreshTokenInvalidException::class);

        $this->service->rotate(Str::random(64));
    }

    public function test_rotate_with_expired_token_throws(): void
    {
        $user = User::factory()->create();

        $pair = $this->service->issue($user);

        $expiredRow = RefreshToken::query()
            ->where('token_hash', hash('sha256', $pair->refreshToken))
            ->sole();
        $expiredRow->expires_at = now()->subMinute();
        $expiredRow->save();

        $this->assertNull($expiredRow->revoked_at);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $expiredRow->access_token_id]);

        $this->expectException(RefreshTokenInvalidException::class);

        $this->service->rotate($pair->refreshToken);
    }

    public function test_logout_deletes_access_token_and_refresh_row_and_spares_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $pair = $this->service->issue($user);
        $otherPair = $this->service->issue($otherUser);

        $accessRow = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();
        $otherAccessRow = PersonalAccessToken::query()->where('tokenable_id', $otherUser->id)->sole();

        $this->service->logout($accessRow->id);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessRow->id]);
        $this->assertDatabaseMissing('refresh_tokens', ['access_token_id' => $accessRow->id]);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherAccessRow->id]);
        $this->assertDatabaseHas('refresh_tokens', [
            'access_token_id' => $otherAccessRow->id,
            'token_hash' => hash('sha256', $otherPair->refreshToken),
        ]);
    }

    public function test_logout_does_not_error_when_access_token_already_deleted(): void
    {
        $user = User::factory()->create();

        $pair = $this->service->issue($user);
        $accessRow = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();

        $this->service->logout($accessRow->id);

        $this->service->logout($accessRow->id);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessRow->id]);
        $this->assertDatabaseMissing('refresh_tokens', ['access_token_id' => $accessRow->id]);
    }
}
