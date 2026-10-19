<?php

namespace App\Exceptions;

class UnauthenticatedException extends ApiException
{
    public function __construct(string $message = 'unauthenticated')
    {
        parent::__construct('UNAUTHENTICATED', $message, 401);
    }
}
