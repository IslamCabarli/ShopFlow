<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Resources\OrderResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Order;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class OrderController extends Controller
    {
        use ApiResponse;

        public function index(Request $request): JsonResponse
        {
            // List view stays light: no items/payments loaded, so no extra queries.
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
    }
