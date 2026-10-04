<?php

    namespace App\Services;

    use App\Exceptions\InvalidOrderTransitionException;
    use App\Models\Order;

    class OrderStateMachine
    {
        private const ORDER_TRANSITIONS = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        private const PAYMENT_TRANSITIONS = [
            'pending' => ['paid', 'failed'],
            'paid' => ['refunded'],
            'failed' => ['pending'],
            'refunded' => [],
        ];

        public function transitionOrderTo(Order $order, string $newStatus): Order
        {
            $allowed = self::ORDER_TRANSITIONS[$order->status] ?? [];

            if (!in_array($newStatus, $allowed, true)) {
                throw new InvalidOrderTransitionException(
                    "Cannot transition order from '{$order->status}' to '{$newStatus}'."
                );
            }

            $order->status = $newStatus;
            $order->save();

            return $order;
        }

        public function transitionPaymentTo(Order $order, string $newStatus): Order
        {
            $allowed = self::PAYMENT_TRANSITIONS[$order->payment_status] ?? [];

            if (!in_array($newStatus, $allowed, true)) {
                throw new InvalidOrderTransitionException(
                    "Cannot transition payment from '{$order->payment_status}' to '{$newStatus}'."
                );
            }

            $order->payment_status = $newStatus;
            $order->save();

            return $order;
        }
    }
