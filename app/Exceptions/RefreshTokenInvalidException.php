<?php

namespace App\Exceptions;

final class RefreshTokenInvalidException extends ApiException
{
    public const ERROR_CODE = 'REFRESH_TOKEN_INVALID';

    public const HTTP_STATUS = 401;

    public function __construct(string $message = 'Invalid refresh token.', mixed $details = null)
    {
        parent::__construct($message, self::ERROR_CODE, self::HTTP_STATUS, $details);
    }
}
