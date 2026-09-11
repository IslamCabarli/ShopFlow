<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@shopflow.test'
        ]);
        User::factory()->count(10)->create();

        $categories = Category::factory()->count(10)->create();

        Product::factory()->count(50)
            ->create()
            ->each(function (Product $product) use ($categories) {
                Inventory::factory()->for($product)->create();
                $product->categories()->attach(
                $categories->random(rand(1,3))->pluck('id')
                );
            });
    }
}
