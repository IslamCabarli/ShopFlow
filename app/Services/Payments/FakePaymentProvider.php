<?php

    namespace App\Services\Payments;

    use App\Contracts\PaymentProviderInterface;
    use App\Models\Payment;

    class FakePaymentProvider implements PaymentProviderInterface
    {
        public function charge(Payment $payment): bool
        {
            // Simulated gateway: always succeeds.
            return true;
        }

        public function refund(Payment $payment): bool
        {
            return true;
        }
    }
