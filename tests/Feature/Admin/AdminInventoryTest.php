<?php

    namespace Tests\Feature\Admin;

    use App\Models\Inventory;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class AdminInventoryTest extends TestCase
    {
        use RefreshDatabase;

        public function test_regular_user_cannot_adjust_inventory(): void
        {
            $product = $this->productWithStock(10);
            Sanctum::actingAs(User::factory()->create());

            $this->postJson("/api/v1/admin/products/{$product->id}/inventory/adjust", ['delta' => 5])
                ->assertStatus(403);

            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 10]);
        }

        public function test_admin_can_restock(): void
        {
            $product = $this->productWithStock(10);
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson("/api/v1/admin/products/{$product->id}/inventory/adjust", ['delta' => 5])
                ->assertStatus(200)
                ->assertJsonPath('data.quantity', 15)
                ->assertJsonPath('data.available', 15);
        }

        public function test_admin_can_write_off_stock(): void
        {
            $product = $this->productWithStock(10);
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson("/api/v1/admin/products/{$product->id}/inventory/adjust", ['delta' => -3])
                ->assertStatus(200)
                ->assertJsonPath('data.quantity', 7);
        }

        public function test_adjustment_cannot_push_stock_below_zero(): void
        {
            $product = $this->productWithStock(10);
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson("/api/v1/admin/products/{$product->id}/inventory/adjust", ['delta' => -11])
                ->assertStatus(409);

            $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 10]);
        }

        public function test_zero_delta_is_rejected(): void
        {
            $product = $this->productWithStock(10);
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson("/api/v1/admin/products/{$product->id}/inventory/adjust", ['delta' => 0])
                ->assertStatus(422);
        }

        public function test_low_stock_filter_lists_only_matching_products_in_ascending_order(): void
        {
            $this->productWithStock(2);
            $this->productWithStock(50);
            $this->productWithStock(5);

            Sanctum::actingAs(User::factory()->admin()->create());

            $response = $this->getJson('/api/v1/admin/inventories?low_stock=5')
                ->assertStatus(200)
                ->assertJsonCount(2, 'data');

            $this->assertEquals([2, 5], array_column($response->json('data'), 'quantity'));
        }

        public function test_adjusting_stock_invalidates_the_cached_product(): void
        {
            $product = $this->productWithStock(10);

            $this->getJson("/api/v1/products/{$product->id}")->assertJsonPath('data.stock', 10);

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson("/api/v1/admin/products/{$product->id}/inventory/adjust", ['delta' => 5])
                ->assertStatus(200);

            $this->getJson("/api/v1/products/{$product->id}")->assertJsonPath('data.stock', 15);
        }

        private function productWithStock(int $quantity): Product
        {
            $product = Product::factory()->create();
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'reserved_quantity' => 0,
            ]);

            return $product;
        }
    }
