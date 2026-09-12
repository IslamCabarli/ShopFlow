<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
}
