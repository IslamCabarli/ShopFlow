<?php

    namespace Tests\Feature;

    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class RateLimitTest extends TestCase
    {
        use RefreshDatabase;

        public function test_login_is_rate_limited_after_too_many_attempts(): void
        {
            $payload = ['email' => 'nobody@example.com', 'password' => 'wrong'];

            for ($i = 0; $i < 5; $i++) {
                $this->postJson('/api/v1/auth/login', $payload);
            }

            $response = $this->postJson('/api/v1/auth/login', $payload);

            $response->assertStatus(429);
        }

        public function test_public_product_listing_is_rate_limited(): void
        {
            for ($i = 0; $i < 60; $i++) {
                $this->getJson('/api/v1/products');
            }

            $response = $this->getJson('/api/v1/products');

            $response->assertStatus(429);
        }
    }
