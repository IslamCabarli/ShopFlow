<?php

    namespace Tests\Feature\Payment;

    use App\Models\Cart;
    use App\Models\Inventory;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class PaymentTest extends TestCase
    {
        use RefreshDatabase;

        public function test_successful_checkout_confirms_order_and_pays_payment(): void
        {
            [$user] = $this->checkout();

            $this->assertDatabaseHas('orders', [
                'user_id' => $user->id,
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
        }

        public function test_cannot_charge_an_already_paid_order(): void
        {
            [$user, $order] = $this->checkout();

            $response = $this->postJson("/api/v1/orders/{$order->id}/charge");

            $response->assertStatus(422);
        }

        public function test_user_cannot_view_or_charge_another_users_order(): void
        {
            [, $order] = $this->checkout();

            $otherUser = User::factory()->create();
            Sanctum::actingAs($otherUser);

            $response = $this->postJson("/api/v1/orders/{$order->id}/charge");

            $response->assertStatus(403);
        }

        private function checkout(): array
        {
            $user = User::factory()->create();
            $product = Product::factory()->create(['price' => 50, 'discount_price' => null]);
            Inventory::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
            $cart = Cart::factory()->create(['user_id' => $user->id]);
            $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/checkout', [
                'shipping_name' => 'Test',
                'shipping_address' => 'Addr',
                'shipping_city' => 'Baku',
                'shipping_country' => 'Azerbaijan',
            ]);

            $order = \App\Models\Order::where('user_id', $user->id)->latest()->first();

            return [$user, $order];
        }
    }
