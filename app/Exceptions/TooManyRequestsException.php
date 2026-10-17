<?php

namespace App\Exceptions;

final class TooManyRequestsException extends ApiException
{
    public const ERROR_CODE = 'TOO_MANY_REQUESTS';

    public const HTTP_STATUS = 429;

    public function __construct(string $message = 'Too many attempts, please try again shortly.', mixed $details = null)
    {
        parent::__construct($message, self::ERROR_CODE, self::HTTP_STATUS, $details);
    }
}
