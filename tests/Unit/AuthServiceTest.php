<?php

namespace Tests\Unit;

use App\DataTransferObjects\LoginData;
use App\DataTransferObjects\RegisterData;
use App\Exceptions\InvalidCredentialsException;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuthService::class);
    }

    public function test_unknown_phone_throws_invalid_credentials(): void
    {
        try {
            $this->service->login(new LoginData('09120000000', 'password123'));
            $this->fail('should throw');
        } catch (InvalidCredentialsException $e) {
            $this->assertSame('phone or password is incorrect', $e->getMessage());
        }
    }

    public function test_wrong_password_throws_same_exception_as_unknown_phone(): void
    {
        User::factory()->create(['phone' => '09120000000']);

        try {
            $this->service->login(new LoginData('09120000000', 'wrong-password'));
            $this->fail('should throw');
        } catch (InvalidCredentialsException $e) {
            $unknownPhoneMessage = null;

            try {
                $this->service->login(new LoginData('09129999999', 'wrong-password'));
            } catch (InvalidCredentialsException $caught) {
                $unknownPhoneMessage = $caught->getMessage();
            }

            $this->assertSame($unknownPhoneMessage, $e->getMessage());
        }
    }

    public function test_login_returns_token_pair(): void
    {
        User::factory()->create(['phone' => '09120000000', 'password' => 'password123']);

        $pair = $this->service->login(new LoginData('09120000000', 'password123'));

        $this->assertNotEmpty($pair->accessToken);
        $this->assertNotEmpty($pair->refreshToken);
    }

    public function test_register_creates_user_and_issues_pair(): void
    {
        $data = new RegisterData('09120000000', 'Ali', 'Rezaei', 'male', '0468534474', 'password123');

        $pair = $this->service->register($data);

        $this->assertDatabaseHas('users', [
            'phone' => '09120000000',
            'national_id' => '0468534474',
        ]);
        $this->assertNotEmpty($pair->accessToken);
        $this->assertDatabaseCount('refresh_tokens', 1);
        $this->assertNotSame($pair->refreshToken, RefreshToken::first()->token_hash);
    }
}
