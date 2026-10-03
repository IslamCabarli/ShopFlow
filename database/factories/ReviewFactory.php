<?php

    namespace Database\Factories;

    use App\Models\Review;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * @extends Factory<Review>
     */
    class ReviewFactory extends Factory
    {
        public function definition(): array
        {
            return [
                'rating' => fake()->numberBetween(1, 5),
                'comment' => fake()->optional()->sentence(),
            ];
        }
    }
