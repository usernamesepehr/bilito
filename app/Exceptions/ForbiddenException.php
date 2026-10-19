<?php

namespace App\Exceptions;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'forbidden')
    {
        parent::__construct('FORBIDDEN', $message, 403);
    }
}
