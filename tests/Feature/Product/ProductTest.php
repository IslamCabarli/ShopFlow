<?php

namespace Tests\Feature\Product;

use App\Models\Product;
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
}
