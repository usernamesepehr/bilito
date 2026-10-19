<?php

namespace App\Exceptions;

class TooManyRequestsException extends ApiException
{
    /** @param array<string, int> $headers */
    public function __construct(int $retryAfterSeconds)
    {
        parent::__construct(
            'TOO_MANY_REQUESTS',
            'too many requests',
            429,
            ['retry_after' => $retryAfterSeconds],
        );
    }
}
