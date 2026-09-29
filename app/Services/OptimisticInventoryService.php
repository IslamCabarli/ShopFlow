<?php

    namespace App\Services;

    use App\Exceptions\ConcurrentUpdateException;
    use App\Exceptions\InsufficientStockException;
    use App\Models\Inventory;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;

    /**
     * Prototype: the same "decrement stock safely" problem CheckoutService
     * solves with SELECT ... FOR UPDATE (pessimistic locking), solved here
     * with optimistic locking instead — for comparison, not for production use.
     *
     * Pessimistic: lock the row up front, make everyone else WAIT.
     * Optimistic:  don't lock anything, write with a version check, and
     *              RETRY if someone else won the race in between.
     */
    class OptimisticInventoryService
    {
        private const MAX_ATTEMPTS = 3;

        public function decrement(int $productId, int $quantity): Inventory
        {
            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
                // Plain read — no lock, no blocking. Other processes can
                // read and write this row freely while we're here.
                $inventory = Inventory::where('product_id', $productId)->firstOrFail();

                $available = $inventory->quantity - $inventory->reserved_quantity;

                if ($quantity > $available) {
                    throw new InsufficientStockException(
                        "Insufficient stock for product {$productId}."
                    );
                }

                // The WHERE version = X is the entire trick: this UPDATE only
                // matches a row if nobody has changed it since we read it.
                $affected = DB::table('inventories')
                    ->where('id', $inventory->id)
                    ->where('version', $inventory->version)
                    ->update([
                        'quantity' => $inventory->quantity - $quantity,
                        'version' => $inventory->version + 1,
                        'updated_at' => now(),
                    ]);

                if ($affected === 1) {
                    return $inventory->refresh();
                }

                // affected === 0 means someone else updated this row between
                // our read and our write. Their change is already committed,
                // so we loop, re-read the now-current row, and re-check stock.
                Log::info('Optimistic lock conflict on inventory, retrying', [
                    'product_id' => $productId,
                    'attempt' => $attempt,
                ]);
            }

            throw new ConcurrentUpdateException(
                'Could not update inventory after ' . self::MAX_ATTEMPTS . ' attempts due to concurrent modification.'
            );
        }
    }
