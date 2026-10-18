<?php

    namespace Tests\Feature\Auth;

    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class TokenExpiryTest extends TestCase
    {
        use RefreshDatabase;

        public function test_token_stops_working_after_the_configured_expiration(): void
        {
            $user = User::factory()->create();

            $token = $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertStatus(200)->json('data.token');

            $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(200);

            $this->travel(25)->hours();


            $this->app['auth']->forgetGuards();

            $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
        }
    }
