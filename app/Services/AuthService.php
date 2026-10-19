<?php

namespace App\Services;

use App\DataTransferObjects\LoginData;
use App\DataTransferObjects\RegisterData;
use App\DataTransferObjects\TokenPair;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    public function register(RegisterData $data): TokenPair
    {
        $user = $this->users->create($data);

        return $this->tokens->issue($user);
    }

    public function login(LoginData $data): TokenPair
    {
        $user = $this->users->findByPhone($data->phone);

        $hash = $user !== null
            ? $user->password
            : Hash::make('dummy-password-for-timing-equality');

        $passwordMatches = Hash::check($data->password, $hash);

        if ($user === null || ! $passwordMatches) {
            throw new InvalidCredentialsException;
        }

        return $this->tokens->issue($user);
    }

    public function logout(User $user): void
    {
        $accessToken = $user->currentAccessToken();

        if ($accessToken !== null) {
            $this->tokens->revokeForAccess($accessToken->id);
            $accessToken->delete();
        }
    }
}
