<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
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
}
