<?php

namespace App\Exceptions;

class RefreshTokenInvalidException extends ApiException
{
    public function __construct(string $message = 'refresh token is invalid')
    {
        parent::__construct('REFRESH_TOKEN_INVALID', $message, 401);
    }
}
