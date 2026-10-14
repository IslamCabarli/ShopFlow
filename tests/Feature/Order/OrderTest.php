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

    class OrderTest extends TestCase
    {
        use RefreshDatabase;

        public function test_guest_cannot_list_orders(): void
        {
            $this->getJson('/api/v1/orders')->assertStatus(401);
        }

        public function test_user_sees_only_their_own_orders(): void
        {
            $user = User::factory()->create();
            Order::factory()->count(2)->create(['user_id' => $user->id]);
            Order::factory()->count(3)->create();

            Sanctum::actingAs($user);

            $this->getJson('/api/v1/orders')
                ->assertStatus(200)
                ->assertJsonCount(2, 'data');
        }

        public function test_user_can_view_own_order_with_items_and_payments(): void
        {
            $user = User::factory()->create();
            $order = $this->checkoutFor($user);

            Sanctum::actingAs($user);

            $this->getJson("/api/v1/orders/{$order->id}")
                ->assertStatus(200)
                ->assertJsonPath('data.order_number', $order->order_number)
                ->assertJsonCount(1, 'data.items')
                ->assertJsonCount(1, 'data.payments');
        }

        public function test_user_cannot_view_another_users_order(): void
        {
            $owner = User::factory()->create();
            $order = $this->checkoutFor($owner);

            Sanctum::actingAs(User::factory()->create());

            $this->getJson("/api/v1/orders/{$order->id}")->assertStatus(403);
        }

        public function test_admin_can_view_any_order(): void
        {
            $owner = User::factory()->create();
            $order = $this->checkoutFor($owner);

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->getJson("/api/v1/orders/{$order->id}")->assertStatus(200);
        }

        private function checkoutFor(User $user): Order
        {
            $product = Product::factory()->create(['price' => 50, 'discount_price' => null]);
            Inventory::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
            $cart = Cart::factory()->create(['user_id' => $user->id]);
            $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

            Sanctum::actingAs($user);

            $this->postJson('/api/v1/checkout', [
                'shipping_name' => 'Test',
                'shipping_address' => 'Addr',
                'shipping_city' => 'Baku',
                'shipping_country' => 'Azerbaijan',
            ])->assertStatus(201);

            return Order::where('user_id', $user->id)->latest()->first();
        }
    }
