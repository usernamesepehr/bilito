<?php

namespace App\Exceptions;

class InvalidCredentialsException extends ApiException
{
    public function __construct()
    {
        parent::__construct('INVALID_CREDENTIALS', 'phone or password is incorrect', 401);
    }
}
