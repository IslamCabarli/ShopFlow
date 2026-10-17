<?php

    namespace Tests\Feature\Checkout;

    use App\Events\OrderCreated;
    use App\Models\Cart;
    use App\Models\Inventory;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\Event;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class IdempotencyTest extends TestCase
    {
        use RefreshDatabase;

        public function test_same_key_replays_the_original_order_without_side_effects(): void
        {
            Event::fake([OrderCreated::class]);

            $user = User::factory()->create();
            $this->prepareCart($user, stock: 10, quantity: 2);
            Sanctum::actingAs($user);

            $first = $this->postJson('/api/v1/checkout', $this->payload(), ['Idempotency-Key' => 'key-123']);

            $first->assertStatus(201)
                ->assertHeaderMissing('Idempotent-Replayed')
                // Regression: the response must reflect the post-payment state.
                ->assertJsonPath('data.status', 'confirmed')
                ->assertJsonPath('data.payment_status', 'paid');

            // The cart is empty now; a retry must still succeed, not fail with "cart is empty".
            $this->assertDatabaseCount('cart_items', 0);

            $second = $this->postJson('/api/v1/checkout', $this->payload(), ['Idempotency-Key' => 'key-123']);

            $second->assertStatus(201)
                ->assertHeader('Idempotent-Replayed', 'true')
                ->assertJsonPath('data.id', $first->json('data.id'))
                ->assertJsonPath('data.order_number', $first->json('data.order_number'));

            $this->assertDatabaseCount('orders', 1);
            $this->assertDatabaseCount('payments', 1);
            $this->assertDatabaseHas('inventories', ['quantity' => 8]);
            Event::assertDispatchedTimes(OrderCreated::class, 1);
        }

        public function test_reusing_a_key_with_a_different_payload_is_rejected(): void
        {
            $user = User::factory()->create();
            $this->prepareCart($user);
            Sanctum::actingAs($user);

            $this->postJson('/api/v1/checkout', $this->payload(), ['Idempotency-Key' => 'key-abc'])
                ->assertStatus(201);

            $this->postJson(
                '/api/v1/checkout',
                $this->payload(['shipping_city' => 'Ganja']),
                ['Idempotency-Key' => 'key-abc']
            )->assertStatus(422);

            $this->assertDatabaseCount('orders', 1);
        }

        public function test_checkout_without_a_key_still_works_and_is_not_deduplicated(): void
        {
            $user = User::factory()->create();
            $this->prepareCart($user);
            Sanctum::actingAs($user);

            $this->postJson('/api/v1/checkout', $this->payload())
                ->assertStatus(201)
                ->assertHeaderMissing('Idempotent-Replayed');

            $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'idempotency_key' => null]);
        }

        public function test_the_same_key_from_different_users_does_not_collide(): void
        {
            $userA = User::factory()->create();
            $userB = User::factory()->create();
            $this->prepareCart($userA);
            $this->prepareCart($userB);

            Sanctum::actingAs($userA);
            $this->postJson('/api/v1/checkout', $this->payload(), ['Idempotency-Key' => 'shared-key'])
                ->assertStatus(201);

            Sanctum::actingAs($userB);
            $this->postJson('/api/v1/checkout', $this->payload(), ['Idempotency-Key' => 'shared-key'])
                ->assertStatus(201)
                ->assertHeaderMissing('Idempotent-Replayed');

            $this->assertDatabaseCount('orders', 2);
        }

        public function test_overlong_key_is_rejected(): void
        {
            $user = User::factory()->create();
            $this->prepareCart($user);
            Sanctum::actingAs($user);

            $this->postJson('/api/v1/checkout', $this->payload(), ['Idempotency-Key' => str_repeat('a', 101)])
                ->assertStatus(422);

            $this->assertDatabaseCount('orders', 0);
        }

        private function prepareCart(User $user, int $stock = 10, int $quantity = 1): Product
        {
            $product = Product::factory()->create(['price' => 100, 'discount_price' => null]);
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => $stock,
                'reserved_quantity' => 0,
            ]);
            $cart = Cart::factory()->create(['user_id' => $user->id]);
            $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => $quantity]);

            return $product;
        }

        private function payload(array $overrides = []): array
        {
            return array_merge([
                'shipping_name' => 'Test',
                'shipping_address' => 'Addr',
                'shipping_city' => 'Baku',
                'shipping_country' => 'Azerbaijan',
            ], $overrides);
        }
    }
