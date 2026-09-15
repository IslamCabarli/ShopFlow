<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Product;
class CartService
{
    /**
     * Create a new class instance.
     */
    public function getOrCreateCart(User $user): Cart
    {
        return $user->cart()->firstOrCreate([]);
    }

    public function addItem(User $user, Product $product, int $quantity): CartItem
    {

        $cart = $this->getOrCreateCart($user);

        $availableStock =$product->inventory->quantity - $product->inventory->reserved_quantity;

        if ($availableStock < $quantity) {
            throw new InsufficientStockException(
                'Insufficient stock for this product.'
            );
        }
        $cartItem = $cart->cartItems()
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;

            if ($newQuantity > $availableStock) {
                throw new InsufficientStockException(
                    'Insufficient stock for this product.'
                );
            }

            $cartItem->update(['quantity' => $newQuantity,]);

            return $cartItem->fresh();
        }

        return $cart->cartItems()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        $product =$item->product;

        $availableStock = $product->inventory->quantity - $product->inventory->reserved_quantity;

        if ($quantity > $availableStock) {
            throw new InsufficientStockException(
                'Insufficient stock for this product.'
            );
        }

        $item->update([
            'quantity' => $quantity,
        ]);

        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->cartItems()->delete();
    }
}
