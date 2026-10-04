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

        public function charge(Order $order, PaymentService $paymentService): JsonResponse
        {
            $this->authorize('view', $order);

            $payment = $paymentService->charge($order->payments()->latest()->first());

            return $this->success(
                new PaymentResource($payment),
                'Payment processed successfully'
            );
        }
    }
