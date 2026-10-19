<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $phone = '09120000000'): array
    {
        User::factory()->create(['phone' => $phone, 'password' => 'password123']);

        return $this->postJson('/api/v1/auth/login', [
            'phone' => $phone,
            'password' => 'password123',
        ])->json('data');
    }

    public function test_logout_revokes_only_current_device(): void
    {
        $deviceA = $this->login();
        $deviceB = $this->login('09121111111');

        $this->withToken($deviceA['access_token'])
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        $this->resetAuthState();

        $this->withToken($deviceA['access_token'])
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);

        $this->withToken($deviceB['access_token'])
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $deviceA['refresh_token']])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'REFRESH_TOKEN_INVALID');

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $deviceB['refresh_token']])
            ->assertStatus(200);
    }
}
