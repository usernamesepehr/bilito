<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

final class ApiError
{
    public static function response(
        string $code,
        string $message,
        int $status,
        mixed $details = null,
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status, $headers);
    }
}
