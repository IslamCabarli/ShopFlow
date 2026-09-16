<?php

namespace Tests\Feature\Auth;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    /**
     * A basic feature test example.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register',[
            'name' => 'islam',
            'email' => 'islam@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'islam@gmail.com',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'user',
                'token',
            ],
            'message',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        $user = User::factory()->create([
            'email' => 'islam@gmail.com'
        ]);
        $response = $this->postJson('/api/v1/auth/register',[
            'name' => 'islam',
            'email' => 'islam@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_register_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register',[
            'name' => 'islam',
            'email' => 'islam@gmail.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

    }
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'islam@gmail.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'user',
                'token',
            ],
            'message',
        ]);
    }
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'islam@gmail.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'islam@gmail.com',
            'password' => 'PassWord123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_non_admin_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $user = User::factory()->admin()->create();
        $product = Product::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(201);
    }
}
