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

    public function test_add_existing_product_updates_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(201);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity', 5);

        $this->assertDatabaseCount('cart_items', 1);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_update_item_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = $user->cart->cartItems()->first();

        $response = $this->patchJson(
            "/api/v1/cart/items/{$item->id}",
            ['quantity' => 5]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.quantity', 5);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
        ]);
    }

    public function test_remove_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = $user->cart->cartItems()->first();

        $response = $this->deleteJson(
            "/api/v1/cart/items/{$item->id}"
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_clear_cart(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->deleteJson('/api/v1/cart');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = $owner->cart->cartItems()->first();

        Sanctum::actingAs($otherUser);

        $response = $this->patchJson(
            "/api/v1/cart/items/{$item->id}",
            ['quantity' => 5]
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 2,
        ]);
    }

    public function test_user_cannot_delete_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = $owner->cart->cartItems()->first();

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson(
            "/api/v1/cart/items/{$item->id}"
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_add_item_with_invalid_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $this->assertDatabaseCount('cart_items', 0);
    }
}
