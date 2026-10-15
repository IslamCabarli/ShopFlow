<?php

    namespace Tests\Feature\Order;

    use App\Models\Cart;
    use App\Models\Inventory;
    use App\Models\Order;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class OrderLifecycleTest extends TestCase
    {
        use RefreshDatabase;

        public function test_owner_can_cancel_order_and_stock_is_restored(): void
        {
            [$user, $order, $product] = $this->placeOrder(quantity: 2);

            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 8]);

            Sanctum::actingAs($user);

            $this->postJson("/api/v1/orders/{$order->id}/cancel")
                ->assertStatus(200)
                ->assertJsonPath('data.status', 'cancelled')
                ->assertJsonPath('data.payment_status', 'refunded');

            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 10]);
            $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'refunded']);
        }

        public function test_cancelling_twice_restores_stock_only_once(): void
        {
            [$user, $order, $product] = $this->placeOrder(quantity: 2);

            Sanctum::actingAs($user);

            $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertStatus(200);
            $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertStatus(422);

            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 10]);
        }

        public function test_shipped_order_cannot_be_cancelled(): void
        {
            [$user, $order, $product] = $this->placeOrder(quantity: 2);

            Order::whereKey($order->id)->update(['status' => 'shipped']);

            Sanctum::actingAs($user);

            $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertStatus(422);

            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 8]);
        }

        public function test_user_cannot_cancel_another_users_order(): void
        {
            [, $order, $product] = $this->placeOrder(quantity: 2);

            Sanctum::actingAs(User::factory()->create());

            $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertStatus(403);

            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 8]);
        }

        public function test_admin_can_move_order_through_the_full_lifecycle(): void
        {
            [, $order] = $this->placeOrder();

            Sanctum::actingAs(User::factory()->admin()->create());

            foreach (['processing', 'shipped', 'delivered'] as $status) {
                $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => $status])
                    ->assertStatus(200)
                    ->assertJsonPath('data.status', $status);
            }
        }

        public function test_admin_cannot_skip_order_states(): void
        {
            [, $order] = $this->placeOrder();

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'shipped'])
                ->assertStatus(422);

            $this->assertEquals('confirmed', $order->fresh()->status);
        }

        public function test_cancelled_order_cannot_be_shipped(): void
        {
            [, $order, $product] = $this->placeOrder(quantity: 2);

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'cancelled'])
                ->assertStatus(200);

            // Cancelling through the admin endpoint must restore stock too.
            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 10]);

            $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'shipped'])
                ->assertStatus(422);
        }

        public function test_regular_user_cannot_use_admin_status_endpoint(): void
        {
            [$user, $order] = $this->placeOrder();

            Sanctum::actingAs($user);

            $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'processing'])
                ->assertStatus(403);
        }

        public function test_admin_status_endpoint_rejects_unknown_status(): void
        {
            [, $order] = $this->placeOrder();

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'pending'])
                ->assertStatus(422);
        }

        /**
         * Stock starts at 10; the order buys $quantity.
         *
         * @return array{0: User, 1: Order, 2: Product}
         */
        private function placeOrder(int $quantity = 1): array
        {
            $user = User::factory()->create();
            $product = Product::factory()->create(['price' => 50, 'discount_price' => null]);
            Inventory::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
            $cart = Cart::factory()->create(['user_id' => $user->id]);
            $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => $quantity]);

            Sanctum::actingAs($user);

            $this->postJson('/api/v1/checkout', [
                'shipping_name' => 'Test',
                'shipping_address' => 'Addr',
                'shipping_city' => 'Baku',
                'shipping_country' => 'Azerbaijan',
            ])->assertStatus(201);

            $order = Order::where('user_id', $user->id)->latest()->first();

            return [$user, $order, $product];
        }
    }
