<?php

namespace App\Exceptions;

final class InvalidCredentialsException extends ApiException
{
    public const ERROR_CODE = 'INVALID_CREDENTIALS';

    public const HTTP_STATUS = 401;

    public function __construct(string $message = 'Invalid credentials.', mixed $details = null)
    {
        parent::__construct($message, self::ERROR_CODE, self::HTTP_STATUS, $details);
    }
}
