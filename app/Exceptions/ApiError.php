<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

final class ApiError
{
    public static function response(
        string $errorCode,
        string $message,
        int $status,
        mixed $details = null,
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details,
            ],
        ], $status, $headers);
    }
}
