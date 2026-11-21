<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Cart\StoreCartItemRequest;
    use App\Http\Requests\Cart\UpdateCartItemRequest;
    use App\Http\Resources\CartItemResource;
    use App\Http\Resources\CartResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\CartItem;
    use App\Models\Product;
    use App\Services\CartService;
    use Dedoc\Scramble\Attributes\Response;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class CartController extends Controller
    {
        use ApiResponse;

        public function __construct(
            protected CartService $cartService
        ) {
        }

        /**
         * Get my cart
         *
         * The cart is created on first access.
         */
        public function index(Request $request): JsonResponse
        {
            $cart = $this->cartService
                ->getOrCreateCart($request->user());

            $cart->load('cartItems.product');

            /**
             * The current user's cart.
             *
             * @body array{data: CartResource, message: string}
             */
            return $this->success(
                new CartResource($cart)
            );
        }

        /**
         * Add a product to the cart
         *
         * Adding a product that is already in the cart increases its quantity.
         */
        #[Response(409, 'Not enough stock for the requested quantity.', type: 'array{message: string, errors: array{}}')]
        public function storeItem(StoreCartItemRequest $request): JsonResponse
        {
            $product = Product::findOrFail(
                $request->integer('product_id')
            );

            $item = $this->cartService->addItem(
                $request->user(),
                $product,
                $request->integer('quantity')
            );

            /**
             * Item added.
             *
             * @status 201
             * @body array{data: CartItemResource, message: string}
             */
            return $this->success(
                new CartItemResource($item->load('product')),
                'Item added to cart',
                201
            );
        }

        /**
         * Change an item quantity
         *
         * Sets the quantity to the given value (not a delta).
         */
        #[Response(409, 'Not enough stock for the requested quantity.', type: 'array{message: string, errors: array{}}')]
        public function updateItem(
            UpdateCartItemRequest $request,
            CartItem $item
        ): JsonResponse {
            $this->authorize('update', $item);

            $item = $this->cartService->updateItemQuantity(
                $item,
                $request->integer('quantity')
            );

            /**
             * Item updated.
             *
             * @body array{data: CartItemResource, message: string}
             */
            return $this->success(
                new CartItemResource($item->load('product')),
                'Cart item updated successfully'
            );
        }

        /**
         * Remove an item from the cart
         */
        public function destroyItem(CartItem $item): JsonResponse
        {
            $this->authorize('delete', $item);

            $this->cartService->removeItem($item);

            /**
             * Item removed.
             *
             * @body array{data: array{}, message: string}
             */
            return $this->success(
                null,
                'Cart item removed successfully'
            );
        }

        /**
         * Empty the cart
         */
        public function clear(Request $request): JsonResponse
        {
            $cart = $this->cartService->getOrCreateCart(
                $request->user()
            );

            $this->cartService->clear($cart);

            /**
             * Cart emptied.
             *
             * @body array{data: array{}, message: string}
             */
            return $this->success(
                null,
                'Cart cleared successfully'
            );
        }
    }
