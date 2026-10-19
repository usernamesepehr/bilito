<?php

namespace Tests\Unit;

use App\Exceptions\RefreshTokenInvalidException;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_issue_creates_hashed_refresh_token(): void
    {
        $user = User::factory()->create();

        $pair = $this->service->issue($user);

        $this->assertNotSame($pair->refreshToken, $pair->refreshToken ? hash('sha256', $pair->refreshToken) : '');

        $row = RefreshToken::query()->where('token_hash', hash('sha256', $pair->refreshToken))->first();

        $this->assertNotNull($row);
        $this->assertSame($user->id, $row->user_id);
        $this->assertDatabaseMissing('refresh_tokens', ['token_hash' => $pair->refreshToken]);
        $this->assertTrue($row->expires_at->isFuture());
    }

    public function test_rotate_revokes_old_and_issues_new(): void
    {
        $user = User::factory()->create();
        $pair = $this->service->issue($user);
        $oldHash = hash('sha256', $pair->refreshToken);
        $oldAccessTokenId = RefreshToken::where('token_hash', $oldHash)->value('access_token_id');

        $new = $this->service->rotate($pair->refreshToken);

        $this->assertNotNull(RefreshToken::where('token_hash', $oldHash)->first()->revoked_at);
        $this->assertNull(RefreshToken::where('token_hash', hash('sha256', $new->refreshToken))->first()->revoked_at);
        $this->assertDatabaseHas('refresh_tokens', ['token_hash' => hash('sha256', $new->refreshToken)]);
        $this->assertNotSame($oldHash, hash('sha256', $new->refreshToken));
    }

    public function test_rotate_keeps_chain_id(): void
    {
        $user = User::factory()->create();
        $pair = $this->service->issue($user);
        $chainId = RefreshToken::where('token_hash', hash('sha256', $pair->refreshToken))->value('chain_id');

        $new = $this->service->rotate($pair->refreshToken);

        $this->assertSame($chainId, RefreshToken::where('token_hash', hash('sha256', $new->refreshToken))->value('chain_id'));
    }

    public function test_reuse_of_rotated_token_revokes_chain_and_throws(): void
    {
        $user = User::factory()->create();
        $pair = $this->service->issue($user);
        $new = $this->service->rotate($pair->refreshToken);

        try {
            $this->service->rotate($pair->refreshToken);
            $this->fail('reuse should throw');
        } catch (RefreshTokenInvalidException) {
        }

        $chainId = RefreshToken::where('token_hash', hash('sha256', $pair->refreshToken))->value('chain_id');
        $this->assertNotNull(RefreshToken::where('token_hash', hash('sha256', $new->refreshToken))->first()->revoked_at);
        $this->assertSame(0, RefreshToken::where('chain_id', $chainId)->whereNull('revoked_at')->count());
    }

    public function test_unknown_refresh_token_throws(): void
    {
        $this->expectException(RefreshTokenInvalidException::class);
        $this->service->rotate('nonsense-token');
    }

    public function test_expired_refresh_token_throws(): void
    {
        $user = User::factory()->create();
        $pair = $this->service->issue($user);
        RefreshToken::where('token_hash', hash('sha256', $pair->refreshToken))
            ->update(['expires_at' => now()->subDay()]);

        $this->expectException(RefreshTokenInvalidException::class);
        $this->service->rotate($pair->refreshToken);
    }
}
