<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    public function render(Request $request):JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [],
        ],409);
    }
}
