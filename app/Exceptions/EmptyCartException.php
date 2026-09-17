<?php

    namespace App\Exceptions;

    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Exception;

    class EmptyCartException extends Exception
    {
        public function render(Request $request): JsonResponse
        {
            return response()->json([
                'message' => $this->getMessage(),
                'errors' => [],
            ], 409);
        }
    }
