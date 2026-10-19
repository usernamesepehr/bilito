<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\AuthenticateFromCookie;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

if (! function_exists('rendersJson')) {
    function rendersJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    function errorResponse(string $code, string $message, int $status, ?array $details = null, array $headers = []): JsonResponse
    {
        return response()->json(['error' => [
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ]], $status)->withHeaders($headers);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.cookie' => AuthenticateFromCookie::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! rendersJson($request)) {
                return null;
            }

            return errorResponse('VALIDATION_FAILED', 'validation failed', 422, $e->errors());
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! rendersJson($request)) {
                return null;
            }

            return errorResponse('UNAUTHENTICATED', 'unauthenticated', 401);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! rendersJson($request)) {
                return null;
            }

            $headers = Arr::only($e->getHeaders(), ['Retry-After', 'X-RateLimit-Remaining']);

            return errorResponse('TOO_MANY_REQUESTS', 'too many requests', 429, null, $headers);
        });

        $exceptions->render(function (ApiException $e, Request $request) {
            if (! rendersJson($request)) {
                return null;
            }

            return errorResponse($e->errorCode, $e->getMessage(), $e->status, $e->details);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! rendersJson($request)) {
                return null;
            }

            $code = match (true) {
                $e->getStatusCode() === 403 => 'FORBIDDEN',
                $e->getStatusCode() === 404 => 'NOT_FOUND',
                $e->getStatusCode() >= 500 => 'SERVER_ERROR',
                default => 'HTTP_'.$e->getStatusCode(),
            };

            return errorResponse(
                $code,
                $e->getMessage() ?: 'error',
                $e->getStatusCode(),
                null,
                $e->getHeaders(),
            );
        });
    })->create();
