<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Resources\PaymentResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Order;
    use App\Services\PaymentService;
    use Illuminate\Http\JsonResponse;

    class PaymentController extends Controller
    {
        use ApiResponse;

        /**
         * Charge an order
         *
         * Owner or admin. Returns 422 if the order has already been processed.
         */
        public function charge(Order $order, PaymentService $paymentService): JsonResponse
        {
            $this->authorize('view', $order);

            $payment = $paymentService->charge($order->payments()->latest()->first());

            /**
             * Payment processed.
             *
             * @body array{data: PaymentResource, message: string}
             */
            return $this->success(
                new PaymentResource($payment),
                'Payment processed successfully'
            );
        }
    }
