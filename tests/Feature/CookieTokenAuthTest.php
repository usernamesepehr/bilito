<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CookieTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth.cookie'])->get('/_test/me', fn () => [
            'id' => auth()->id(),
            'phone' => auth()->user()->phone,
        ]);

        Route::middleware(['web', 'auth.cookie', 'admin'])->get('/_test/admin', fn () => 'ok');
    }

    public function test_authenticates_user_from_http_only_token_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->withTokenCookie(
            $user->createToken('api', ['read'])->plainTextToken
        )->getJson('/_test/me');

        $response->assertOk()->assertJson(['id' => $user->id]);
    }

    public function test_rejects_request_without_token_cookie(): void
    {
        $this->getJson('/_test/me')->assertUnauthorized();
    }

    public function test_rejects_invalid_token_cookie(): void
    {
        $this->withTokenCookie('invalid-token')->getJson('/_test/me')->assertUnauthorized();
    }

    public function test_rejects_token_that_is_expired(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('api', ['*'], now()->subMinute())->plainTextToken;

        $this->withTokenCookie($token)->getJson('/_test/me')->assertUnauthorized();
    }

    public function test_admin_route_allows_token_with_is_admin_ability(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('api', ['is_admin'])->plainTextToken;

        $this->withTokenCookie($token)->getJson('/_test/admin')->assertOk();
    }

    public function test_admin_route_forbids_token_without_is_admin_ability(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('api', ['read'])->plainTextToken;

        $this->withTokenCookie($token)->getJson('/_test/admin')->assertForbidden();
    }

    public function test_admin_route_rejects_session_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->getJson('/_test/admin')->assertUnauthorized();
    }

    private function withTokenCookie(string $plainTextToken): static
    {
        return $this->withCredentials()
            ->withCookie(config('sanctum.token_cookie'), $plainTextToken);
    }
}
