<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'admin'])->get('/api/v1/_test/admin', fn () => 'ok');
    }

    public function test_admin_user_passes(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth', expiresAt: now()->addMinutes(30))->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/_test/admin')
            ->assertOk();
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth', expiresAt: now()->addMinutes(30))->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/_test/admin')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_unauthenticated_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/_test/admin')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
