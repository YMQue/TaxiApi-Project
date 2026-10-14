<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(string $message, mixed $data = null, int $status = 200, ?array $meta = null): JsonResponse
{
    $body = [
        'success' => true,
        'message' => $message,
        'data'    => $data,
        'error'   => null,
    ];

    if ($meta !== null) {
        $body['meta'] = $meta;
    }

    return response()->json($body, $status);
}
    protected function failure(string $message, mixed $error = null, int $status = 422): JsonResponse
{
    return response()->json([
        'success' => false,
        'message' => $message,
        'data'    => null,
        'error'   => $error ?? $message,
    ], $status);
}
}
