<?php

    namespace Tests\Feature\Checkout;

    use App\Models\Cart;
    use App\Models\Inventory;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;
    class CheckoutTest extends TestCase
    {
        use RefreshDatabase;

        public function test_user_can_checkout_cart(): void
        {
            $user = User::factory()->create();

            $product = Product::factory()->create([
                'price' => 100,
                'discount_price' => null,
            ]);

            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => 10,
                'reserved_quantity' => 0,
            ]);

            $cart = Cart::factory()->create([
                'user_id' => $user->id,
            ]);

            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/checkout', $this->shippingData());

            $response->assertStatus(201);

            // Derive the expected values from the created order instead of
            // assuming id=1: Postgres sequences are not rolled back between tests.
            $orderId = $response->json('data.id');

            $response->assertJsonPath(
                'data.order_number',
                'ORD-' . now()->format('Ymd') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT)
            );

            $response->assertJsonPath('data.subtotal', '200.00');
            $response->assertJsonPath('data.total', '200.00');

            $this->assertDatabaseHas('orders', [
                'id' => $orderId,
                'user_id' => $user->id,
                'subtotal' => 200,
                'discount' => 0,
                'total' => 200,
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);

            $this->assertDatabaseHas('order_items', [
                'order_id' => $orderId,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => 2,
                'unit_price' => 100,
                'subtotal' => 200,
            ]);

            $this->assertDatabaseHas('payments', [
                'order_id' => $orderId,
                'amount' => 200,
                'status' => 'paid',
                'provider' => 'fake',
            ]);

            $this->assertDatabaseCount('cart_items', 0);

            $this->assertDatabaseHas('inventories', [
                'product_id' => $product->id,
                'quantity' => 8,
            ]);
        }
        public function test_checkout_fails_when_stock_is_insufficient(): void
        {
            $user = User::factory()->create();
            $product = $this->createProductWithStock(stock: 1);
            $cart = Cart::factory()->create(['user_id' => $user->id]);

            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/checkout', $this->shippingData());

            $response->assertStatus(409);

            // Rollback: the order created inside the transaction must not exist
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('order_items', 0);
            $this->assertDatabaseCount('payments', 0);

            // Inventory untouched, cart untouched
            $this->assertDatabaseHas('inventories', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
            $this->assertDatabaseCount('cart_items', 1);
        }

        public function test_checkout_succeeds_when_buying_exact_remaining_stock(): void
        {
            $user = User::factory()->create();
            $product = $this->createProductWithStock(stock: 2);
            $cart = Cart::factory()->create(['user_id' => $user->id]);

            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/checkout', $this->shippingData());

            $response->assertStatus(201);

            $this->assertDatabaseHas('inventories', [
                'product_id' => $product->id,
                'quantity' => 0,
            ]);
            $this->assertDatabaseCount('orders', 1);
        }

        public function test_checkout_fails_with_empty_cart(): void
        {
            $user = User::factory()->create();
            Cart::factory()->create(['user_id' => $user->id]);

            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/checkout', $this->shippingData());

            $response->assertStatus(409);
            $this->assertDatabaseCount('orders', 0);
        }

        public function test_checkout_rolls_back_all_stock_changes_when_one_item_fails(): void
        {
            $user = User::factory()->create();


            $productA = $this->createProductWithStock(stock: 10);
            $productB = $this->createProductWithStock(stock: 1);

            $cart = Cart::factory()->create(['user_id' => $user->id]);

            $cart->cartItems()->create([
                'product_id' => $productA->id,
                'quantity' => 2,
            ]);
            $cart->cartItems()->create([
                'product_id' => $productB->id,
                'quantity' => 5,
            ]);

            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/checkout', $this->shippingData());

            $response->assertStatus(409);


            $this->assertDatabaseHas('inventories', [
                'product_id' => $productA->id,
                'quantity' => 10,
            ]);
            $this->assertDatabaseHas('inventories', [
                'product_id' => $productB->id,
                'quantity' => 1,
            ]);

            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('order_items', 0);
            $this->assertDatabaseCount('payments', 0);
            $this->assertDatabaseCount('cart_items', 2);
        }


        private function createProductWithStock(int $stock): Product
        {
            $product = Product::factory()->create([
                'price' => 100,
                'discount_price' => null,
            ]);

            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => $stock,
                'reserved_quantity' => 0,
            ]);

            return $product;
        }


        private function shippingData(): array
        {
            return [
                'shipping_name' => 'Test User',
                'shipping_address' => 'Test Address 123',
                'shipping_city' => 'Baku',
                'shipping_country' => 'Azerbaijan',
                'shipping_postal_code' => 'AZ1000',
            ];
        }
    }
