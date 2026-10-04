<?php

    namespace App\Services;

    use App\Contracts\PaymentProviderInterface;
    use App\Models\Payment;

    class PaymentService
    {
        public function __construct(
            private PaymentProviderInterface $provider,
            private OrderStateMachine $stateMachine
        ) {
        }

        public function charge(Payment $payment): Payment
        {
            $succeeded = $this->provider->charge($payment);

            $payment->status = $succeeded ? 'paid' : 'failed';
            $payment->paid_at = $succeeded ? now() : null;
            $payment->save();

            $this->stateMachine->transitionPaymentTo($payment->order, $payment->status);

            if ($succeeded) {
                $this->stateMachine->transitionOrderTo($payment->order, 'confirmed');
            }

            return $payment;
        }
    }
