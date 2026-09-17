<?php

namespace App\Services;

use App\Exceptions\EmptyCartException;
use App\Models\Order;
use App\Models\User;

class CheckoutService
{
    /**
     * Create a new class instance.
     */
    public function checkout(User $user, array $shippingData): Order
    {
        $cart = $user->cart()->with('cartItems.product.inventory')->first();

        if(!$cart || $cart->cartItems->isEmpty()) {
            throw new EmptyCartException('Your cart is empty');
        }
    }

}
