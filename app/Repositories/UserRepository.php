<?php

namespace App\Repositories;

use App\DataTransferObjects\RegisterData;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findByPhone(string $phone): ?User
    {
        return User::query()->where('phone', $phone)->first();
    }

    public function create(RegisterData $data): User
    {
        return User::query()->create([
            'phone' => $data->phone,
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'gender' => $data->gender,
            'national_id' => $data->nationalId,
            'password' => $data->password,
        ]);
    }
}
