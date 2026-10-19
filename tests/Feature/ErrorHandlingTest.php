<?php

namespace Tests\Feature;

use App\Exceptions\ForbiddenException;
use App\Exceptions\InvalidCredentialsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/validation', fn () => throw ValidationException::withMessages([
            'phone' => ['this phone is invalid'],
        ]));
        Route::get('/_test/needs-auth', fn () => auth()->id())->middleware('auth:sanctum');
        Route::get('/_test/invalid-credentials', fn () => throw new InvalidCredentialsException)->name('ic');
        Route::get('/_test/forbidden', fn () => throw new ForbiddenException)->name('fb');
        Route::get('/_test/forbidden-http', fn () => abort(403));
        Route::get('/_test/unhandled', fn () => throw new \RuntimeException('boom'));
    }

    public function test_validation_errors_use_standard_shape(): void
    {
        $response = $this->getJson('/_test/validation');

        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('phone', $response->json('error.details'));
    }

    public function test_api_exception_renders_standard_shape(): void
    {
        $this->getJson('/_test/invalid-credentials')
            ->assertStatus(401)
            ->assertJson(['error' => [
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'phone or password is incorrect',
                'details' => null,
            ]]);
    }

    public function test_unauthenticated_returns_json_shape(): void
    {
        $this->getJson('/_test/needs-auth', [])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_http_403_returns_forbidden_code(): void
    {
        $this->getJson('/_test/forbidden-http')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_forbidden_exception_renders_forbidden_code(): void
    {
        $this->getJson('/_test/forbidden')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_unhandled_exception_returns_server_error_shape(): void
    {
        $response = $this->getJson('/_test/unhandled');

        $response->assertStatus(500)->assertJsonPath('error.code', 'SERVER_ERROR');

        $this->assertStringNotContainsString('boom', $response->getContent());
    }
}
