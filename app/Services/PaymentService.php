<?php

    namespace App\Services;

    use App\Contracts\PaymentProviderInterface;
    use App\Exceptions\InvalidOrderTransitionException;
    use App\Models\Payment;
    use Illuminate\Support\Facades\DB;

    class PaymentService
    {
        public function __construct(
            private PaymentProviderInterface $provider,
            private OrderStateMachine $stateMachine
        ) {
        }

        public function charge(Payment $payment): Payment
        {
            if ($payment->order->payment_status !== 'pending') {
                throw new InvalidOrderTransitionException(
                    'This order has already been processed and cannot be charged again.'
                );
            }

            $succeeded = $this->provider->charge($payment);

            return DB::transaction(function () use ($payment, $succeeded) {
                $payment->status = $succeeded ? 'paid' : 'failed';
                $payment->paid_at = $succeeded ? now() : null;
                $payment->save();

                $this->stateMachine->transitionPaymentTo($payment->order, $payment->status);

                if ($succeeded) {
                    $this->stateMachine->transitionOrderTo($payment->order, 'confirmed');
                }

                return $payment;
            });
        }

        public function refund(Payment $payment): Payment
        {
            if ($payment->status !== 'paid') {
                throw new InvalidOrderTransitionException('Only paid payments can be refunded.');
            }

            if (! $this->provider->refund($payment)) {
                throw new \RuntimeException("Provider refused the refund for payment {$payment->id}.");
            }

            return DB::transaction(function () use ($payment) {
                $payment->status = 'refunded';
                $payment->save();

                $this->stateMachine->transitionPaymentTo($payment->order, 'refunded');

                return $payment;
            });
        }
    }
