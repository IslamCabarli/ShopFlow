<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Order\UpdateOrderStatusRequest;
    use App\Http\Resources\OrderResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Order;
    use App\Services\OrderService;
    use Dedoc\Scramble\Attributes\QueryParameter;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class OrderController extends Controller
    {
        use ApiResponse;

        /**
         * List my orders
         *
         * Newest first, 15 per page. Items and payments are not included; use the single-order endpoint for those.
         */
        #[QueryParameter('page', 'Page number.', type: 'int', default: 1)]
        public function index(Request $request): JsonResponse
        {
            $orders = $request->user()
                ->orders()
                ->latest()
                ->paginate(15);

            /**
             * A page of the user's orders.
             *
             * @body array{data: OrderResource[], meta: array{current_page: int, last_page: int, per_page: int, total: int}, message: string}
             */
            return $this->successPaginated(
                OrderResource::collection($orders),
                'Orders retrieved successfully'
            );
        }

        /**
         * Get an order
         *
         * Owner or admin. Includes items and payments.
         */
        public function show(Order $order): JsonResponse
        {
            $this->authorize('view', $order);

            /**
             * The order with its items and payments.
             *
             * @body array{data: OrderResource, message: string}
             */
            return $this->success(
                new OrderResource($order->load(['orderItems', 'payments']))
            );
        }

        /**
         * Cancel an order
         *
         * Owner or admin. Allowed while the order is pending, confirmed or processing: stock is restored and a paid payment is refunded. Other statuses return 422.
         */
        public function cancel(Order $order, OrderService $orderService): JsonResponse
        {
            $this->authorize('cancel', $order);

            /**
             * Order cancelled.
             *
             * @body array{data: OrderResource, message: string}
             */
            return $this->success(
                new OrderResource($orderService->cancel($order)),
                'Order cancelled successfully'
            );
        }

        /**
         * Change an order status
         *
         * Admin only. Allowed path: confirmed, processing, shipped, delivered. Sending `cancelled` behaves like the cancel endpoint. Invalid transitions return 422.
         */
        public function updateStatus(
            UpdateOrderStatusRequest $request,
            Order $order,
            OrderService $orderService
        ): JsonResponse {
            /**
             * Status updated.
             *
             * @body array{data: OrderResource, message: string}
             */
            return $this->success(
                new OrderResource($orderService->updateStatus($order, $request->validated('status'))),
                'Order status updated successfully'
            );
        }
    }
