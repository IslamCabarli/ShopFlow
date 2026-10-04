<?php

    namespace App\Contracts;

    use App\Models\Payment;

    interface PaymentProviderInterface
    {
        public function charge(Payment $payment): bool;

        public function refund(Payment $payment): bool;
    }
