<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        config(['sanctum.guard' => []]);

        Sanctum::getAccessTokenFromRequestUsing(
            fn (Request $request) => $request->cookie(config('sanctum.token_cookie'))
        );

        if (! Auth::guard('sanctum')->check()) {
            throw new AuthenticationException('Unauthenticated.', ['sanctum']);
        }

        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
