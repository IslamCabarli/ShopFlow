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
