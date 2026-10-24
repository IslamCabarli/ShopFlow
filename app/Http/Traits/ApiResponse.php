<?php

    namespace App\Http\Traits;

    use Illuminate\Http\JsonResponse;

    trait ApiResponse
    {
        /**
         * Paginated list inside the standard envelope.
         *
         * @template TResource
         * @template TStatus of int
         *
         * @param  TResource  $paginatedResource
         * @param  TStatus  $status
         * @return JsonResponse<array{data: TResource, meta: array{current_page: int, last_page: int, per_page: int, total: int}, message: string}, TStatus>
         */
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

        /**
         * Single payload inside the standard envelope.
         *
         * @template TData
         * @template TStatus of int
         *
         * @param  TData  $data
         * @param  TStatus  $status
         * @return JsonResponse<array{data: TData, message: string}, TStatus>
         */
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
