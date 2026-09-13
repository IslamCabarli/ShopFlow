<?php

    namespace App\Http\Traits;

    use Illuminate\Http\JsonResponse;

    trait ApiResponse
    {

        protected function successPaginated($paginatedResource, string $message = '', int $status = 200): JsonResponse
        {
            return response()->json([
                'data' => $paginatedResource->response()->getData(true)['data'],
                'meta' => [
                    'current_page' => $paginatedResource->currentPage(),
                    'last_page' => $paginatedResource->lastPage(),
                    'per_page' => $paginatedResource->perPage(),
                    'total' => $paginatedResource->total(),
                ],
                'message' => $message,
            ], $status);
        }
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
