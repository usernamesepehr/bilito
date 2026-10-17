<?php

namespace App\Repositories;

use App\Dtos\RegisterData;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function create(RegisterData $data): User
    {
        return User::create([
            'phone' => $data->phone,
            'password' => $data->password,
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'gender' => $data->gender,
            'national_id' => $data->nationalId,
        ]);
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }
}
