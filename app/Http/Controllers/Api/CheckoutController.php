<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\CheckoutRequest;
    use App\Http\Resources\OrderResource;
    use App\Services\CheckoutService;
    use Illuminate\Http\JsonResponse;

    class CheckoutController extends Controller
    {
        public function __construct(
            protected CheckoutService $checkoutService
        ) {}

        public function store(CheckoutRequest $request): JsonResponse
        {
            $order = $this->checkoutService->checkout(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Checkout completed successfully.',
                'data' => new OrderResource($order),
            ], 201);
        }
    }
