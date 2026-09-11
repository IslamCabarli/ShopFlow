<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $price = fake()->randomFloat(2, 5, 500);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('######'),
            'description' => fake()->paragraph(),
            'sku' => fake()->unique()->numerify('######'),
            'price' => $price,
            'discount_price' => fake()->boolean(30)
                ? fake()->randomFloat(2, 1, $price * 0.9)
                : null,
            'status' => fake()->randomElement([
                'active',
                'inactive',
                'draft',
            ]),


        ];
    }
}
