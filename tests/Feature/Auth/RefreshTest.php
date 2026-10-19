<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_rotates_tokens(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'phone' => '09123456789',
            'firstname' => 'Ali',
            'lastname' => 'Rezaei',
            'gender' => 'male',
            'national_id' => '0468534474',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $oldRefresh = $register->json('data.refresh_token');
        $oldAccess = $register->json('data.access_token');

        $response = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $oldRefresh]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['user', 'access_token', 'refresh_token', 'token_type']]);

        $this->assertNotSame($oldRefresh, $response->json('data.refresh_token'));
        $this->assertNotSame($oldAccess, $response->json('data.access_token'));

        $me = $this->withToken($response->json('data.access_token'))->getJson('/api/v1/auth/me');
        $me->assertOk();
    }

    public function test_old_refresh_token_cannot_be_reused(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'phone' => '09123456789',
            'firstname' => 'Ali',
            'lastname' => 'Rezaei',
            'gender' => 'male',
            'national_id' => '0468534474',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $oldRefresh = $register->json('data.refresh_token');
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $oldRefresh])->assertStatus(200);

        $reuse = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $oldRefresh]);
        $reuse->assertStatus(401)->assertJsonPath('error.code', 'REFRESH_TOKEN_INVALID');

        $this->assertSame(0, RefreshToken::whereNull('revoked_at')->count());
    }

    public function test_unknown_refresh_token_is_invalid(): void
    {
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => 'nonsense'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'REFRESH_TOKEN_INVALID');
    }

    public function test_expired_refresh_token_is_invalid(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'phone' => '09123456789',
            'firstname' => 'Ali',
            'lastname' => 'Rezaei',
            'gender' => 'male',
            'national_id' => '0468534474',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $refresh = $register->json('data.refresh_token');

        $this->travelTo(now()->addDays(31));

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refresh])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'REFRESH_TOKEN_INVALID');
    }
}
