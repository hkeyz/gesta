<?php

namespace App\MobileApi\V1\Support;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success($data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function failure(
        string $message,
        array $errors = [],
        int $status = 400,
        string $code = 'request_failed'
    ): JsonResponse {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if (! empty($errors)) {
            $payload['error']['details'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function realtimeMeta(int $refreshAfterSeconds = 15): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'refresh_after_seconds' => $refreshAfterSeconds,
        ];
    }
}
