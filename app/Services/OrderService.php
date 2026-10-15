<?php

    namespace App\Services;

    use App\Models\Inventory;
    use App\Models\Order;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use Throwable;

    class OrderService
    {
        public function __construct(
            private OrderStateMachine $stateMachine,
            private PaymentService $paymentService
        ) {
        }

        public function cancel(Order $order): Order
        {
            $cancelled = DB::transaction(function () use ($order) {
                // Lock the order first: a concurrent second cancel waits here,
                // then fails the state-machine check instead of restoring stock twice.
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                $this->stateMachine->transitionOrderTo($locked, 'cancelled');

                // Same lock order as checkout (product_id ascending) to avoid deadlocks.
                foreach ($locked->orderItems()->orderBy('product_id')->get() as $item) {
                    Inventory::where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail()
                        ->increment('quantity', $item->quantity);
                }

                return $locked;
            });

            // External call, deliberately outside the DB transaction.
            if ($cancelled->payment_status === 'paid') {
                $payment = $cancelled->payments()->where('status', 'paid')->latest()->first();

                if ($payment) {
                    try {
                        $this->paymentService->refund($payment);
                    } catch (Throwable $e) {
                        // Order is already cancelled and stock restored; the refund
                        // stays 'paid' and needs a retry/manual follow-up.
                        Log::error("Refund failed for order {$cancelled->id}: {$e->getMessage()}");
                    }
                }
            }

            Cache::tags(['products'])->flush();

            return Order::with(['orderItems', 'payments'])->findOrFail($cancelled->id);
        }

        public function updateStatus(Order $order, string $status): Order
        {
            // Cancelling must always go through cancel() so stock is restored.
            if ($status === 'cancelled') {
                return $this->cancel($order);
            }

            $updated = DB::transaction(function () use ($order, $status) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                return $this->stateMachine->transitionOrderTo($locked, $status);
            });

            return $updated->load(['orderItems', 'payments']);
        }
    }
