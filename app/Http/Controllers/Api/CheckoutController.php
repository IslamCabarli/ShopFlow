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
        ) {
        }

        public function store(CheckoutRequest $request): JsonResponse
        {
            $result = $this->checkoutService->checkout(
                $request->user(),
                $request->validated(),
                $request->validated('idempotency_key'),
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
