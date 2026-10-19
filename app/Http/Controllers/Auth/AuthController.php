<?php

namespace App\Http\Controllers\Auth;

use App\DataTransferObjects\LoginData;
use App\DataTransferObjects\RegisterData;
use App\DataTransferObjects\TokenPair;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly TokenService $tokens,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $pair = $this->auth->register(new RegisterData(
            phone: $request->phone,
            firstname: $request->firstname,
            lastname: $request->lastname,
            gender: $request->gender,
            nationalId: $request->national_id,
            password: $request->password,
        ));

        return response()->json(['data' => $this->tokenPayload($pair)], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $pair = $this->auth->login(new LoginData($request->phone, $request->password));

        return response()->json(['data' => $this->tokenPayload($pair)]);
    }

    public function refresh(RefreshRequest $request): JsonResponse
    {
        $pair = $this->tokens->rotate($request->refresh_token);

        return response()->json(['data' => $this->tokenPayload($pair)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['data' => null]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function tokenPayload(TokenPair $pair): array
    {
        return [
            'user' => $pair->user !== null ? (new UserResource($pair->user))->resolve() : null,
            'access_token' => $pair->accessToken,
            'access_token_expires_at' => $pair->accessTokenExpiresAt->toIso8601String(),
            'refresh_token' => $pair->refreshToken,
            'refresh_token_expires_at' => $pair->refreshTokenExpiresAt->toIso8601String(),
            'token_type' => 'Bearer',
        ];
    }
}
