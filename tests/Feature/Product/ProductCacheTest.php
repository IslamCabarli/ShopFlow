<?php

    namespace Tests\Feature\Product;

    use App\Models\Product;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\Cache;
    use Tests\TestCase;

    class ProductCacheTest extends TestCase
    {
        use RefreshDatabase;

        public function test_product_show_is_served_from_cache_on_second_request(): void
        {
            $product = Product::factory()->create();

            $this->getJson("/api/v1/products/{$product->id}")->assertStatus(200);

            // Change the DB directly, bypassing the app — if the second
            // request still returns the OLD name, we know it came from cache.
            $product->update(['name' => 'Changed After Cache']);

            $response = $this->getJson("/api/v1/products/{$product->id}");

            $response->assertStatus(200);
            $this->assertNotEquals('Changed After Cache', $response->json('data.name'));
        }

        public function test_updating_product_invalidates_its_cache(): void
        {
            $product = Product::factory()->create();

            $this->getJson("/api/v1/products/{$product->id}")->assertStatus(200);

            $admin = \App\Models\User::factory()->admin()->create();
            \Laravel\Sanctum\Sanctum::actingAs($admin);

            $this->putJson("/api/v1/products/{$product->id}", [
                'name' => 'Updated Name',
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
                'status' => $product->status,
            ])->assertStatus(200);

            $response = $this->getJson("/api/v1/products/{$product->id}");

            $response->assertJsonPath('data.name', 'Updated Name');
        }

        public function test_deleting_product_invalidates_index_cache(): void
        {
            Product::factory()->count(3)->create();

            $this->getJson('/api/v1/products')->assertJsonCount(3, 'data');

            $product = Product::first();

            $admin = \App\Models\User::factory()->admin()->create();
            \Laravel\Sanctum\Sanctum::actingAs($admin);

            $this->deleteJson("/api/v1/products/{$product->id}")->assertStatus(200);

            $response = $this->getJson('/api/v1/products');

            $response->assertJsonCount(2, 'data');
        }

        public function test_different_query_strings_get_separate_cache_entries(): void
        {
            Product::factory()->create(['price' => 50]);
            Product::factory()->create(['price' => 500]);

            $cheap = $this->getJson('/api/v1/products?min_price=0&max_price=100');
            $all = $this->getJson('/api/v1/products');

            $cheap->assertJsonCount(1, 'data');
            $all->assertJsonCount(2, 'data');
        }
    }
