<?php

namespace Tests\Feature\Cart;

use App\Models\Inventory;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_add_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/cart/items',[
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.quantity', 2);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }
}
