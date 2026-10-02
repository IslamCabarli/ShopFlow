<?php

    namespace Tests\Feature\Checkout;

    use App\Models\Cart;
    use App\Models\Coupon;
    use App\Models\Inventory;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class CouponTest extends TestCase
    {
        use RefreshDatabase;

        public function test_percentage_coupon_applies_correct_discount(): void
        {
            $coupon = Coupon::factory()->create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);

            $response = $this->checkout(price: 100, qty: 2, couponCode: 'SAVE10');

            $response->assertStatus(201);
            $response->assertJsonPath('data.subtotal', '200.00');
            $response->assertJsonPath('data.discount', '20.00');
            $response->assertJsonPath('data.total', '180.00');
        }

        public function test_fixed_coupon_applies_correct_discount(): void
        {
            Coupon::factory()->fixed(15)->create(['code' => 'FLAT15']);

            $response = $this->checkout(price: 100, qty: 1, couponCode: 'FLAT15');

            $response->assertStatus(201);
            $response->assertJsonPath('data.discount', '15.00');
            $response->assertJsonPath('data.total', '85.00');
        }

        public function test_maximum_discount_caps_percentage_coupon(): void
        {
            Coupon::factory()->create([
                'code' => 'BIG50',
                'type' => 'percentage',
                'value' => 50,
                'maximum_discount' => 20,
            ]);

            $response = $this->checkout(price: 100, qty: 2, couponCode: 'BIG50');

            // 50% of 200 = 100, but capped at 20
            $response->assertJsonPath('data.discount', '20.00');
        }

        public function test_checkout_fails_with_unknown_coupon_code(): void
        {
            $response = $this->checkout(price: 100, qty: 1, couponCode: 'DOESNOTEXIST');

            $response->assertStatus(422);
            $this->assertDatabaseCount('orders', 0);
        }

        public function test_checkout_fails_with_expired_coupon(): void
        {
            Coupon::factory()->expired()->create(['code' => 'OLD10']);

            $response = $this->checkout(price: 100, qty: 1, couponCode: 'OLD10');

            $response->assertStatus(422);
            $this->assertDatabaseCount('orders', 0);
        }

        public function test_checkout_fails_when_below_minimum_order_amount(): void
        {
            Coupon::factory()->create(['code' => 'MIN100', 'minimum_order_amount' => 100]);

            $response = $this->checkout(price: 50, qty: 1, couponCode: 'MIN100');

            $response->assertStatus(422);
        }

        public function test_checkout_fails_when_usage_limit_reached(): void
        {
            $coupon = Coupon::factory()->create([
                'code' => 'ONCE',
                'usage_limit' => 1,
                'used_count' => 1,
            ]);

            $response = $this->checkout(price: 100, qty: 1, couponCode: 'ONCE');

            $response->assertStatus(422);
        }

        public function test_coupon_usage_is_recorded_and_counter_incremented(): void
        {
            $coupon = Coupon::factory()->create(['code' => 'TRACK10']);

            $this->checkout(price: 100, qty: 1, couponCode: 'TRACK10')->assertStatus(201);

            $this->assertDatabaseHas('coupon_usages', [
                'coupon_id' => $coupon->id,
            ]);
            $this->assertEquals(1, $coupon->fresh()->used_count);
        }

        public function test_checkout_without_coupon_code_still_works(): void
        {
            $response = $this->checkout(price: 100, qty: 1, couponCode: null);

            $response->assertStatus(201);
            $response->assertJsonPath('data.discount', '0.00');
        }

        private function checkout(float $price, int $qty, ?string $couponCode)
        {
            $user = User::factory()->create();
            $product = Product::factory()->create(['price' => $price, 'discount_price' => null]);
            Inventory::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
            $cart = Cart::factory()->create(['user_id' => $user->id]);
            $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => $qty]);

            Sanctum::actingAs($user);

            $payload = [
                'shipping_name' => 'Test',
                'shipping_address' => 'Addr',
                'shipping_city' => 'Baku',
                'shipping_country' => 'Azerbaijan',
            ];

            if ($couponCode) {
                $payload['coupon_code'] = $couponCode;
            }

            return $this->postJson('/api/v1/checkout', $payload);
        }
    }
