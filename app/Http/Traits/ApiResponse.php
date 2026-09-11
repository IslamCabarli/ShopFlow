<?php

    namespace App\Http\Traits;

    use Illuminate\Http\JsonResponse;

    trait ApiResponse
    {
        protected function success(mixed $data = null, string $message = '', int $status = 200): JsonResponse
        {
            return response()->json([
                'data' => $data ?? [],
                'message' => $message,
            ], $status);
        }

        protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
        {
            return response()->json([
                'message' => $message,
                'errors' => $errors ?? [],
            ], $status);
        }
    }
