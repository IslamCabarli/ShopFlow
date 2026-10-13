<?php

    namespace Database\Factories;

    use App\Models\Order;
    use App\Models\User;
    use Illuminate\Database\Eloquent\Factories\Factory;
    use Illuminate\Support\Str;

    /**
     * @extends Factory<Order>
     */
    class OrderFactory extends Factory
    {
        public function definition(): array
        {
            return [
                'user_id' => User::factory(),
                'order_number' => 'ORD-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('#####'),
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => 100,
                'discount' => 0,
                'total' => 100,
                'shipping_name' => fake()->name(),
                'shipping_address' => fake()->streetAddress(),
                'shipping_city' => fake()->city(),
                'shipping_country' => fake()->country(),
                'shipping_postal_code' => fake()->postcode(),
            ];
        }
    }
