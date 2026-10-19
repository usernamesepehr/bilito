<?php

namespace App\Repositories\Contracts;

use App\DataTransferObjects\RegisterData;
use App\Models\User;

interface UserRepositoryInterface
{
    public function findByPhone(string $phone): ?User;

    public function create(RegisterData $data): User;
}
