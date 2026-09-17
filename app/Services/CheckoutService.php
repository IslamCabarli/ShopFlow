<?php

    namespace App\Services;

    use App\Exceptions\EmptyCartException;
    use App\Exceptions\InsufficientStockException;
    use App\Models\Order;
    use App\Models\Payment;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    class CheckoutService
    {
        public function checkout(User $user, array $shippingData): Order
        {
            $cart = $user->cart()
                ->with('cartItems.product.inventory')
                ->first();

            if (!$cart || $cart->cartItems->isEmpty()) {
                throw new EmptyCartException('Your cart is empty.');
            }

            return DB::transaction(function () use ($user, $cart, $shippingData) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => null,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'subtotal' => 0,
                    'discount' => 0,
                    'total' => 0,

                    'shipping_name' => $shippingData['shipping_name'],
                    'shipping_address' => $shippingData['shipping_address'],
                    'shipping_city' => $shippingData['shipping_city'],
                    'shipping_country' => $shippingData['shipping_country'],
                    'shipping_postal_code' => $shippingData['shipping_postal_code'] ?? null,
                ]);

                $order->update([
                    'order_number' => 'ORD-' . now()->format('Ymd') . '-' .
                        str_pad($order->id, 5, '0', STR_PAD_LEFT),
                ]);

                $subtotal = 0;

                foreach ($cart->cartItems as $item) {
                    $product = $item->product;
                    $inventory = $product->inventory;

                    $availableStock = $inventory->quantity - $inventory->reserved_quantity;

                    if ($item->quantity > $availableStock) {
                        throw new InsufficientStockException(
                            "Insufficient stock for {$product->name}."
                        );
                    }

                    $unitPrice = $product->discount_price ?? $product->price;
                    $itemSubtotal = $unitPrice * $item->quantity;

                    $order->orderItems()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $itemSubtotal,
                    ]);

                    $subtotal += $itemSubtotal;
                }

                $discount = 0;
                $total = $subtotal - $discount;

                $order->update([
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'provider' => 'fake',
                    'amount' => $total,
                    'status' => 'pending',
                    'transaction_id' => 'txn_' . Str::uuid(),
                ]);

                $cart->cartItems()->delete();

                return $order->load('orderItems', 'payments');
            });
        }
    }
