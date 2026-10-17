<?php

    namespace App\Services;

    use App\Events\OrderCreated;
    use App\Exceptions\EmptyCartException;
    use App\Exceptions\IdempotencyKeyReuseException;
    use App\Exceptions\InsufficientStockException;
    use App\Exceptions\InvalidCouponException;
    use App\Models\Coupon;
    use App\Models\Inventory;
    use App\Models\Order;
    use App\Models\Payment;
    use App\Models\User;
    use Illuminate\Database\UniqueConstraintViolationException;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    class CheckoutService
    {
        public function __construct(
            private CouponService $couponService,
            private PaymentService $paymentService
        ) {
        }

        public function checkout(User $user, array $shippingData, ?string $idempotencyKey = null): CheckoutResult
        {
            $fingerprint = $idempotencyKey !== null ? $this->fingerprint($shippingData) : null;

            // Must run BEFORE the empty-cart check: a legitimate retry arrives
            // after the first attempt already emptied the cart.
            if ($idempotencyKey !== null) {
                $replay = $this->findReplay($user, $idempotencyKey, $fingerprint);

                if ($replay !== null) {
                    return new CheckoutResult($replay, true);
                }
            }

            $cart = $user->cart()
                ->with('cartItems.product')
                ->first();

            if (!$cart || $cart->cartItems->isEmpty()) {
                throw new EmptyCartException('Your cart is empty.');
            }

            $couponCode = $shippingData['coupon_code'] ?? null;

            try {
                $order = DB::transaction(function () use (
                    $user, $cart, $shippingData, $couponCode, $idempotencyKey, $fingerprint
                ) {
                    // The unique index (user_id, idempotency_key) is the real guard:
                    // if a concurrent request with the same key got here first, this
                    // INSERT waits for it and then fails, before stock is touched.
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

                        'idempotency_key' => $idempotencyKey,
                        'idempotency_fingerprint' => $fingerprint,
                    ]);

                    $order->update([
                        'order_number' => 'ORD-' . now()->format('Ymd') . '-' .
                            str_pad($order->id, 5, '0', STR_PAD_LEFT),
                    ]);

                    $subtotal = 0;

                    foreach ($cart->cartItems->sortBy('product_id') as $item) {
                        $product = $item->product;

                        $inventory = Inventory::where('product_id', $product->id)
                            ->lockForUpdate()
                            ->firstOrFail();

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

                        $inventory->update([
                            'quantity' => $inventory->quantity - $item->quantity,
                        ]);

                        $subtotal += $itemSubtotal;
                    }

                    $discount = 0;
                    $appliedCoupon = null;

                    if ($couponCode) {
                        $appliedCoupon = Coupon::where('code', $couponCode)
                            ->lockForUpdate()
                            ->first();

                        if (!$appliedCoupon) {
                            throw new InvalidCouponException('Coupon code not found.');
                        }

                        $discount = $this->couponService->validate($appliedCoupon, $subtotal, $user);
                    }

                    $total = $subtotal - $discount;

                    $order->update([
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'total' => $total,
                    ]);

                    if ($appliedCoupon) {
                        $this->couponService->recordUsage($appliedCoupon, $user, $order->id);
                    }

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
            } catch (UniqueConstraintViolationException $e) {
                // Two requests with the same key raced and the other one committed first.
                $replay = $idempotencyKey !== null
                    ? $this->findReplay($user, $idempotencyKey, $fingerprint)
                    : null;

                if ($replay === null) {
                    throw $e;
                }

                return new CheckoutResult($replay, true);
            }

            $this->paymentService->charge($order->payments->first());

            // charge() updates its own Order instance, so reload to return the real state.
            $order = $order->fresh(['orderItems', 'payments']);

            Cache::tags(['products'])->flush();
            OrderCreated::dispatch($order);

            return new CheckoutResult($order, false);
        }

        private function findReplay(User $user, string $key, string $fingerprint): ?Order
        {
            $order = Order::withTrashed()
                ->with(['orderItems', 'payments'])
                ->where('user_id', $user->id)
                ->where('idempotency_key', $key)
                ->first();

            if ($order === null) {
                return null;
            }

            if (!hash_equals((string) $order->idempotency_fingerprint, $fingerprint)) {
                throw new IdempotencyKeyReuseException(
                    'This Idempotency-Key was already used with a different request.'
                );
            }

            return $order;
        }

        private function fingerprint(array $data): string
        {
            return hash('sha256', json_encode([
                $data['shipping_name'] ?? null,
                $data['shipping_address'] ?? null,
                $data['shipping_city'] ?? null,
                $data['shipping_country'] ?? null,
                $data['shipping_postal_code'] ?? null,
                $data['coupon_code'] ?? null,
            ], JSON_THROW_ON_ERROR));
        }
    }
