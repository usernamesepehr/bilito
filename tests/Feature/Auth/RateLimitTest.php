<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_sixth_login_attempt_is_throttled(): void
    {
        User::factory()->create(['phone' => '09120000000', 'password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['phone' => '09120000000', 'password' => 'wrong']);
        }

        $response = $this->postJson('/api/v1/auth/login', ['phone' => '09120000000', 'password' => 'wrong']);

        $response->assertStatus(429)
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS')
            ->assertHeader('Retry-After');
    }

    public function test_throttle_is_per_phone(): void
    {
        User::factory()->create(['phone' => '09120000000', 'password' => 'password123']);
        User::factory()->create(['phone' => '09121111111', 'password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['phone' => '09120000000', 'password' => 'wrong']);
        }

        $this->postJson('/api/v1/auth/login', ['phone' => '09121111111', 'password' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_login_throttle_key_shares_bucket_across_phone_spellings(): void
    {
        User::factory()->create(['phone' => '09120000000', 'password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['phone' => '09120000000', 'password' => 'wrong']);
        }

        $this->postJson('/api/v1/auth/login', ['phone' => '+989120000000', 'password' => 'wrong'])
            ->assertStatus(429);
    }
}
