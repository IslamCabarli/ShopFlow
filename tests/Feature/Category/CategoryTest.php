<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    /**
     * A basic feature test example.
     */
    public function test_guest_can_list_categories(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
    }


    public function test_guest_can_view_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'created_at',
            ],
            'message',
        ]);
        $response->assertStatus(200);
    }

    public function test_admin_can_create_category(): void
    {
        $name = $this->faker->name();
        $slug = $this->faker->slug();
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/categories', [
                'name' => $name,
                'slug' => $slug,
            ]);
        $response->assertStatus(201);
        $response->assertJsonStructure([
           'data' => [
               'name',
               'slug'
           ]
        ]);
        $this->assertDatabaseHas('categories', [
            'name' => $name,
            'slug' => $slug,
        ]);


    }

    public function test_non_admin_user_cannot_create_category(): void
    {
        $name = $this->faker->name();
        $slug = $this->faker->slug();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/categories', [
                'name' => $name,
                'slug' => $slug,
            ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('categories', [
            'name' => $name,
            'slug' => $slug,
        ]);


    }

    public function test_admin_can_update_category(): void
    {
        $name = $this->faker->name();
        $slug = $this->faker->slug();
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name' =>$name,
                'slug' => $slug,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'name',
                'slug',
            ]
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    public function test_non_admin_cannot_update_category(): void
    {
        $name = $this->faker->name();
        $slug = $this->faker->slug();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/categories/{$category->id}", [
                'name' => $name,
                'slug' => $slug,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/categories/{$category->id}");
        $response->assertStatus(200);
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_non_admin_cannot_delete_category(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/categories/{$category->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_slug_must_be_unique(): void
    {
        $category = Category::factory()->create([
            'slug' => 'electronics'
        ]);

        $user = User::factory()->admin()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/categories", [
                'name' => 'Another Category',
                'slug' => $category->slug,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }
}
