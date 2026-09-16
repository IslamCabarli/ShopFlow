<?php

    namespace App\Http\Controllers\Api;


    use App\Http\Controllers\Controller;
    use App\Http\Requests\Cart\StoreCartItemRequest;
    use App\Http\Requests\Cart\UpdateCartItemRequest;
    use App\Http\Resources\CartItemResource;
    use App\Http\Resources\CartResource;
    use App\Models\CartItem;
    use App\Models\Product;
    use App\Services\CartService;
    use App\Http\Traits\ApiResponse;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class CartController extends Controller
    {
        use ApiResponse;

        public function __construct(
            protected CartService $cartService
        ) {}

        public function index(Request $request): JsonResponse
        {
            $cart = $this->cartService
                ->getOrCreateCart($request->user());

            $cart->load('cartItems.product');

            return $this->success(
                new CartResource($cart)
            );
        }

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

            return $this->success(
                new CartItemResource($item->load('product')),
                'Item added to cart',
                201
            );
        }

        public function updateItem(
            UpdateCartItemRequest $request,
            CartItem $item
        ): JsonResponse {
            $this->authorize('update', $item);

            $item = $this->cartService->updateItemQuantity(
                $item,
                $request->integer('quantity')
            );

            return $this->success(
                new CartItemResource($item->load('product')),
                'Cart item updated successfully'
            );
        }

        public function destroyItem(CartItem $item): JsonResponse
        {
            $this->authorize('delete', $item);

            $this->cartService->removeItem($item);

            return $this->success(
                null,
                'Cart item removed successfully'
            );
        }

        public function clear(Request $request): JsonResponse
        {
            $cart = $this->cartService->getOrCreateCart(
                $request->user()
            );

            $this->cartService->clear($cart);

            return $this->success(
                null,
                'Cart cleared successfully'
            );
        }
    }
