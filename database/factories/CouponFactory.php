<?php

    namespace Database\Factories;

    use App\Models\Coupon;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * @extends Factory<Coupon>
     */
    class CouponFactory extends Factory
    {
        public function definition(): array
        {
            return [
                'code' => strtoupper(fake()->unique()->bothify('SAVE####')),
                'type' => 'percentage',
                'value' => 10,
                'minimum_order_amount' => 0,
                'maximum_discount' => null,
                'usage_limit' => null,
                'per_user_limit' => null,
                'used_count' => 0,
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => true,
            ];
        }

        public function fixed(float $amount): static
        {
            return $this->state(fn () => ['type' => 'fixed', 'value' => $amount]);
        }

        public function expired(): static
        {
            return $this->state(fn () => ['expires_at' => now()->subDay()]);
        }

        public function inactive(): static
        {
            return $this->state(fn () => ['is_active' => false]);
        }
    }
