<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\CheckoutRequest;
    use App\Http\Resources\OrderResource;
    use App\Http\Traits\ApiResponse;
    use App\Services\CheckoutService;
    use Illuminate\Http\JsonResponse;

    class CheckoutController extends Controller
    {
        use ApiResponse;

        public function __construct(
            protected CheckoutService $checkoutService
        ) {}

        public function store(CheckoutRequest $request): JsonResponse
        {
            $order = $this->checkoutService->checkout(
                $request->user(),
                $request->validated()
            );

            return $this->success(
                new OrderResource($order),
                'Checkout completed successfully.',
                201
            );
        }
    }
