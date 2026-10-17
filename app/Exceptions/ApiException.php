<?php

namespace App\Exceptions;

use RuntimeException;

abstract class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $httpStatus,
        public readonly mixed $details = null,
    ) {
        parent::__construct($message);
    }
}
