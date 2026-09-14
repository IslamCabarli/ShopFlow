<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    /**
     * A basic feature test example.
     */
    public function test_admin_can_create_product(): void
    {
        $name =$this->faker->word;
        $description =$this->faker->text;
        $slug =$this->faker->slug;
        $sku =$this->faker->word;
        $status = $this->faker->randomElement(['active','inactive']);
        $price =$this->faker->randomFloat(10,2);
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('admin')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/products',[
                'name' => $name,
                'description' => $description,
                'slug' => $slug,
                'sku' => $sku,
                'status'=>$status,
                'price' => $price,

            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas(
            'products',
            [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'slug' => $slug,
                'sku' => $sku,
                'status'=>$status,

            ]
        );
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create();
        $name =$this->faker->word;
        $description =$this->faker->text;
        $slug =$this->faker->slug;
        $sku =$this->faker->word;
        $status = $this->faker->randomElement(['active','inactive']);
        $price =$this->faker->randomFloat(10,2);
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('admin')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/products/{$product->id}",
            [
                'name' => $name,
                'description' => $description,
                'slug' => $slug,
                'sku' => $sku,
                'status'=>$status,
                'price' => $price,
            ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $name,
            'slug' => $slug,
        ]);

    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('admin')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_can_be_retrieved(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('admin')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/products/{$product->id}");
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
        ]);


    }

    public function test_product_validation(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('admin')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/products",[
                'name' => '',
                'description' => 1233,
                'sku' => '',
                'status'=>'',
                'price' => '',
            ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'name',
                'description',
                'sku',
                'status',
                'price',
            ]
        ]);
    }

    public function test_products_are_paginated(): void
    {
        Product::factory(20)->create();
        $response = $this->getJson("/api/v1/products");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [],
            'meta' =>
                [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ],
            'message'
        ]);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonPath('meta.total', 20);
    }

    public function test_products_can_be_filtered(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $productA->categories()->attach($categoryA);
        $productB->categories()->attach($categoryB);

        $response = $this->getJson(
            "/api/v1/products?category={$categoryA->id}"
        );

        $response->assertStatus(200);


        $response->assertJsonFragment([
            'id' => $productA->id,
        ]);

        $response->assertJsonMissing([
            'id' => $productB->id,
        ]);
    }

    public function test_products_can_be_sorted(): void
    {
        $productA = Product::factory()->create(['price' => 300]);
        $productB = Product::factory()->create(['price' => 100]);
        $productC = Product::factory()->create(['price' => 200]);

        $response = $this->getJson('/api/v1/products?sort=price_asc');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertSame([
            $productB->id,
            $productC->id,
            $productA->id,
        ], array_column($data, 'id'));
    }
}
