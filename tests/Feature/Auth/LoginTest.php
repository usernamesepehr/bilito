<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_tokens(): void
    {
        User::factory()->create(['phone' => '09120000000', 'password' => 'password123']);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '09120000000',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['user', 'access_token', 'refresh_token', 'token_type']]);
    }

    public function test_wrong_password_and_unknown_phone_return_identical_responses(): void
    {
        User::factory()->create(['phone' => '09120000000', 'password' => 'password123']);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'phone' => '09120000000',
            'password' => 'wrong-password',
        ]);

        $unknownPhone = $this->postJson('/api/v1/auth/login', [
            'phone' => '09129999999',
            'password' => 'wrong-password',
        ]);

        $wrongPassword->assertStatus(401);
        $this->assertSame(
            $unknownPhone->getContent(),
            $wrongPassword->getContent(),
        );
        $unknownPhone->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_invalid_phone_format_is_validation_error(): void
    {
        $this->postJson('/api/v1/auth/login', ['phone' => '123', 'password' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}
