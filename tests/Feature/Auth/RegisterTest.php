<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'phone' => '09123456789',
            'firstname' => 'Ali',
            'lastname' => 'Rezaei',
            'gender' => 'male',
            'national_id' => '0468534474',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_register_returns_tokens_and_creates_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => [
                'user' => ['id', 'phone', 'firstname', 'lastname', 'gender', 'national_id'],
                'access_token', 'access_token_expires_at', 'refresh_token', 'refresh_token_expires_at', 'token_type',
            ]])
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertDatabaseHas('users', ['phone' => '09123456789']);
        $this->assertDatabaseMissing('users', ['password' => 'password123']);
    }

    public function test_register_normalizes_phone(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload(['phone' => '+989123456789']))
            ->assertStatus(201);

        $this->assertDatabaseHas('users', ['phone' => '09123456789']);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        User::factory()->create(['phone' => '09123456789']);

        $this->postJson('/api/v1/auth/register', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertStringContainsString('phone', json_encode($this->postJson('/api/v1/auth/register', $this->validPayload())->json('error.details')));
    }

    public function test_duplicate_national_id_is_rejected(): void
    {
        User::factory()->create(['national_id' => '0468534474']);

        $this->postJson('/api/v1/auth/register', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_invalid_national_checksum_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload(['national_id' => '0468534479']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_repeated_national_digits_are_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload(['national_id' => '1111111111']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_invalid_gender_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload(['gender' => 'other']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_short_password_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_is_admin_cannot_be_set(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload(['is_admin' => true]))
            ->assertStatus(201);

        $this->assertDatabaseHas('users', ['phone' => '09123456789', 'is_admin' => false]);
    }
}
