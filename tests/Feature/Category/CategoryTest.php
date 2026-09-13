<?php

namespace Tests\Feature\Category;

use App\Models\Category;
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
}
