<?php

    namespace App\Console\Commands;

    use App\Models\Cart;
    use App\Models\Inventory;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Console\Command;

    class RaceSetup extends Command
    {
        protected $signature = 'race:setup';
        protected $description = 'Temporary: 1 product with stock=1 and 2 users who both want it';

        public function handle(): int
        {
            $product = Product::factory()->create(['price' => 100, 'discount_price' => null]);

            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'reserved_quantity' => 0,
            ]);

            foreach ([1, 2] as $i) {
                $user = User::factory()->create();
                $cart = Cart::factory()->create(['user_id' => $user->id]);
                $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
                $this->info("User {$i}: id={$user->id}");
            }

            $this->info("Product id={$product->id}");

            return self::SUCCESS;
        }
    }
