<?php

namespace App\Repositories\Contracts;

use App\Dtos\RegisterData;
use App\Models\User;

interface UserRepositoryInterface
{
    public function create(RegisterData $data): User;

    public function findByPhone(string $phone): ?User;
}
