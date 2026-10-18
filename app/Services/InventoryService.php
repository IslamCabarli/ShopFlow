<?php

    namespace App\Services;

    use App\Exceptions\InsufficientStockException;
    use App\Models\Inventory;
    use App\Models\Product;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;

    class InventoryService
    {
        public function adjust(Product $product, int $delta): Inventory
        {
            $inventory = DB::transaction(function () use ($product, $delta) {
                $inventory = Inventory::where('product_id', $product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $newQuantity = $inventory->quantity + $delta;

                if ($newQuantity < max(0, $inventory->reserved_quantity)) {
                    throw new InsufficientStockException(
                        'Adjustment would reduce stock below zero or below the reserved quantity.'
                    );
                }

                $inventory->quantity = $newQuantity;
                $inventory->save();

                return $inventory;
            });

            Cache::tags(['products'])->flush();

            return $inventory;
        }
    }
