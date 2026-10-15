<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Order\UpdateOrderStatusRequest;
    use App\Http\Resources\OrderResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Order;
    use App\Services\OrderService;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class OrderController extends Controller
    {
        use ApiResponse;

        public function index(Request $request): JsonResponse
        {
            $orders = $request->user()
                ->orders()
                ->latest()
                ->paginate(15);

            return $this->successPaginated(
                OrderResource::collection($orders),
                'Orders retrieved successfully'
            );
        }

        public function show(Order $order): JsonResponse
        {
            $this->authorize('view', $order);

            return $this->success(
                new OrderResource($order->load(['orderItems', 'payments']))
            );
        }

        public function cancel(Order $order, OrderService $orderService): JsonResponse
        {
            $this->authorize('cancel', $order);

            return $this->success(
                new OrderResource($orderService->cancel($order)),
                'Order cancelled successfully'
            );
        }

        public function updateStatus(
            UpdateOrderStatusRequest $request,
            Order $order,
            OrderService $orderService
        ): JsonResponse {
            return $this->success(
                new OrderResource($orderService->updateStatus($order, $request->validated('status'))),
                'Order status updated successfully'
            );
        }
    }
