<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_authenticated_user_without_password(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth', expiresAt: now()->addMinutes(30))->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'phone', 'firstname', 'lastname', 'gender', 'national_id']]);

        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_me_without_token_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_me_with_expired_token_is_unauthenticated(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth', expiresAt: now()->addMinutes(30))->plainTextToken;

        $this->travel(40)->minutes();

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_me_with_revoked_token_is_unauthenticated(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth', expiresAt: now()->addMinutes(30))->plainTextToken;
        $user->tokens()->delete();

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_me_with_garbage_token_is_unauthenticated(): void
    {
        $this->withToken('garbage')->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
