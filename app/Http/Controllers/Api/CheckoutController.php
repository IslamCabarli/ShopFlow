<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\CheckoutRequest;
    use App\Http\Resources\OrderResource;
    use App\Http\Traits\ApiResponse;
    use App\Services\CheckoutService;
    use Dedoc\Scramble\Attributes\HeaderParameter;
    use Illuminate\Http\JsonResponse;

    class CheckoutController extends Controller
    {
        use ApiResponse;

        public function __construct(
            protected CheckoutService $checkoutService
        ) {
        }

        /**
         * Place an order from the current cart
         *
         * Creates the order, reserves stock, charges the payment and empties the cart in one transaction.
         */
        #[HeaderParameter(
            'Idempotency-Key',
            'Optional (max 100 characters). Makes the request safe to retry: replaying the same key with the same payload returns the original order with an `Idempotent-Replayed: true` header; a different payload returns 422.',
            type: 'string'
        )]
        public function store(CheckoutRequest $request): JsonResponse
        {
            $result = $this->checkoutService->checkout(
                $request->user(),
                $request->validated(),
                $request->header('Idempotency-Key'),
            );

            $response = $this->success(
                new OrderResource($result->order),
                $result->replayed
                    ? 'Checkout was already completed for this Idempotency-Key.'
                    : 'Checkout completed successfully.',
                201
            );

            if ($result->replayed) {
                $response->header('Idempotent-Replayed', 'true');
            }

            return $response;
        }
    }
