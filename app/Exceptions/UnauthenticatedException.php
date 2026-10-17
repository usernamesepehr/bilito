<?php

namespace App\Exceptions;

final class UnauthenticatedException extends ApiException
{
    public const ERROR_CODE = 'UNAUTHENTICATED';

    public const HTTP_STATUS = 401;

    public function __construct(string $message = 'Unauthenticated.', mixed $details = null)
    {
        parent::__construct($message, self::ERROR_CODE, self::HTTP_STATUS, $details);
    }
}
