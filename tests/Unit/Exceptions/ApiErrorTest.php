<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\ApiError;
use App\Exceptions\ApiException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\RefreshTokenInvalidException;
use App\Exceptions\TooManyRequestsException;
use App\Exceptions\UnauthenticatedException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ApiErrorTest extends TestCase
{
    public static function exception_shape_provider(): array
    {
        return [
            'invalid credentials' => [InvalidCredentialsException::class, 'INVALID_CREDENTIALS', 401, 'Invalid credentials.'],
            'unauthenticated' => [UnauthenticatedException::class, 'UNAUTHENTICATED', 401, 'Unauthenticated.'],
            'refresh token invalid' => [RefreshTokenInvalidException::class, 'REFRESH_TOKEN_INVALID', 401, 'Invalid refresh token.'],
            'too many requests' => [TooManyRequestsException::class, 'TOO_MANY_REQUESTS', 429, 'Too many attempts, please try again shortly.'],
        ];
    }

    #[DataProvider('exception_shape_provider')]
    public function test_each_exception_is_a_runtime_exception_with_the_pinned_code_status_and_default_message(
        string $exception,
        string $errorCode,
        int $httpStatus,
        string $defaultMessage,
    ): void {
        $thrown = new $exception;

        $this->assertInstanceOf(RuntimeException::class, $thrown);
        $this->assertSame($errorCode, $thrown->errorCode);
        $this->assertSame($httpStatus, $thrown->httpStatus);
        $this->assertSame($defaultMessage, $thrown->getMessage());
        $this->assertNull($thrown->details);
    }

    public function test_response_wraps_code_message_and_details_under_error_with_status_and_headers(): void
    {
        $response = ApiError::response('X', 'm', 422, ['f' => 'e'], ['X-H' => '1']);

        $this->assertSame(422, $response->status());
        $this->assertSame('1', $response->headers->get('X-H'));
        $this->assertSame(
            ['error' => ['code' => 'X', 'message' => 'm', 'details' => ['f' => 'e']]],
            $response->getData(true),
        );
    }

    public function test_response_keeps_the_details_key_present_even_when_details_are_null(): void
    {
        $response = ApiError::response('X', 'm', 401);

        $this->assertSame(
            ['error' => ['code' => 'X', 'message' => 'm', 'details' => null]],
            $response->getData(true),
        );
    }

    public function test_an_exception_maps_onto_the_standard_response_shape(): void
    {
        $thrown = new TooManyRequestsException(details: ['retry_after' => 30]);

        $response = ApiError::response(
            $thrown->errorCode,
            $thrown->getMessage(),
            $thrown->httpStatus,
            $thrown->details,
        );

        $this->assertSame(429, $response->status());
        $this->assertSame(
            ['error' => [
                'code' => 'TOO_MANY_REQUESTS',
                'message' => 'Too many attempts, please try again shortly.',
                'details' => ['retry_after' => 30],
            ]],
            $response->getData(true),
        );
    }

    public function test_the_base_exception_is_abstract(): void
    {
        $this->assertTrue((new \ReflectionClass(ApiException::class))->isAbstract());
    }
}
