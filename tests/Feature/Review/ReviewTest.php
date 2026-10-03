<?php

namespace Tests\Feature\Review;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_review_a_purchased_product(): void
    {
        $user = User::factory()->create();
        $product = $this->buyProductFor($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Great product!',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
        ]);
    }


    public function test_user_cannot_review_unpurchased_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
            'rating' => 5,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_review_same_product_twice(): void
    {
        $user = User::factory()->create();
        $product = $this->buyProductFor($user);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 4])
            ->assertStatus(201);

        $response = $this->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 5]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('reviews', 1);
    }

    private function buyProductFor(User $user): Product
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
        ]);

        return $product;
    }
}
