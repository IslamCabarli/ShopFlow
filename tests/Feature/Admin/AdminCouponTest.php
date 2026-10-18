<?php

    namespace Tests\Feature\Admin;

    use App\Models\Cart;
    use App\Models\Coupon;
    use App\Models\Inventory;
    use App\Models\Order;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class AdminCouponTest extends TestCase
    {
        use RefreshDatabase;

        public function test_guest_and_regular_user_cannot_access_admin_coupons(): void
        {
            $this->getJson('/api/v1/admin/coupons')->assertStatus(401);

            Sanctum::actingAs(User::factory()->create());

            $this->getJson('/api/v1/admin/coupons')->assertStatus(403);
        }

        public function test_admin_can_create_a_coupon_and_the_code_is_normalized(): void
        {
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson('/api/v1/admin/coupons', [
                'code' => ' summer20 ',
                'type' => 'percentage',
                'value' => 20,
            ])
                ->assertStatus(201)
                ->assertJsonPath('data.code', 'SUMMER20')
                ->assertJsonPath('data.used_count', 0)
                ->assertJsonPath('data.is_active', true);

            $this->assertDatabaseHas('coupons', ['code' => 'SUMMER20']);
        }

        public function test_percentage_above_100_is_rejected(): void
        {
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson('/api/v1/admin/coupons', [
                'code' => 'TOOMUCH', 'type' => 'percentage', 'value' => 150,
            ])->assertStatus(422);

            $this->assertDatabaseCount('coupons', 0);
        }

        public function test_duplicate_code_is_rejected_case_insensitively(): void
        {
            Coupon::factory()->create(['code' => 'DUPLICATE']);
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson('/api/v1/admin/coupons', [
                'code' => 'duplicate', 'type' => 'fixed', 'value' => 5,
            ])->assertStatus(422);
        }

        public function test_expiry_must_be_after_start(): void
        {
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson('/api/v1/admin/coupons', [
                'code' => 'BADDATES', 'type' => 'fixed', 'value' => 5,
                'starts_at' => '2030-02-01 00:00:00',
                'expires_at' => '2030-01-01 00:00:00',
            ])->assertStatus(422);
        }

        public function test_admin_can_deactivate_a_coupon(): void
        {
            $coupon = Coupon::factory()->create();
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->patchJson("/api/v1/admin/coupons/{$coupon->id}", ['is_active' => false])
                ->assertStatus(200)
                ->assertJsonPath('data.is_active', false);
        }

        public function test_changing_type_requires_resending_the_value(): void
        {
            $coupon = Coupon::factory()->fixed(500)->create();
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->patchJson("/api/v1/admin/coupons/{$coupon->id}", ['type' => 'percentage'])
                ->assertStatus(422);
        }

        public function test_unused_coupon_can_be_deleted(): void
        {
            $coupon = Coupon::factory()->create();
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->deleteJson("/api/v1/admin/coupons/{$coupon->id}")->assertStatus(200);

            $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
        }

        public function test_used_coupon_cannot_be_deleted(): void
        {
            $coupon = Coupon::factory()->create();
            $order = Order::factory()->create();
            $coupon->usages()->create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'used_at' => now(),
            ]);

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->deleteJson("/api/v1/admin/coupons/{$coupon->id}")->assertStatus(409);

            $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
        }

        public function test_admin_created_coupon_works_at_checkout_regardless_of_input_case(): void
        {
            Sanctum::actingAs(User::factory()->admin()->create());

            $this->postJson('/api/v1/admin/coupons', [
                'code' => 'welcome10', 'type' => 'percentage', 'value' => 10,
            ])->assertStatus(201);

            $user = User::factory()->create();
            $product = Product::factory()->create(['price' => 100, 'discount_price' => null]);
            Inventory::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
            $cart = Cart::factory()->create(['user_id' => $user->id]);
            $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

            Sanctum::actingAs($user);

            $this->postJson('/api/v1/checkout', [
                'shipping_name' => 'Test',
                'shipping_address' => 'Addr',
                'shipping_city' => 'Baku',
                'shipping_country' => 'Azerbaijan',
                'coupon_code' => 'welcome10',
            ])
                ->assertStatus(201)
                ->assertJsonPath('data.discount', '10.00')
                ->assertJsonPath('data.total', '90.00');
        }
    }
