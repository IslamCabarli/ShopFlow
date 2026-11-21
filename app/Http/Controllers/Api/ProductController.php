<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Filters\ProductFilter;
    use App\Http\Requests\Product\StoreProductRequest;
    use App\Http\Requests\Product\UpdateProductRequest;
    use App\Http\Resources\ProductResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Product;
    use Dedoc\Scramble\Attributes\QueryParameter;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    class ProductController extends Controller
    {
        use ApiResponse;

        private const CACHE_TTL = 300; // 5 minutes

        /**
         * List products
         *
         * Public. Results are paginated (15 per page) and cached for 5 minutes per distinct query string.
         */
        #[QueryParameter('page', 'Page number.', type: 'int', default: 1)]
        #[QueryParameter('category', 'Only products in this category (category ID).', type: 'int')]
        #[QueryParameter('min_price', 'Minimum price, inclusive.', type: 'float')]
        #[QueryParameter('max_price', 'Maximum price, inclusive.', type: 'float')]
        #[QueryParameter('search', 'Case-insensitive match against the product name.', type: 'string')]
        #[QueryParameter('sort', 'One of: price_asc, price_desc, newest, oldest.', type: 'string')]
        public function index(Request $request, ProductFilter $filter): JsonResponse
        {
            $cacheKey = 'products:index:' . md5($request->getQueryString() ?? 'all');

            $products = Cache::tags(['products'])->remember(
                $cacheKey,
                self::CACHE_TTL,
                function () use ($request, $filter) {
                    $query = Product::query()->with(['categories', 'inventory']);
                    $query = $filter->apply($query, $request);

                    return $query->paginate(15);
                }
            );

            /**
             * A page of products.
             *
             * @body array{data: ProductResource[], meta: array{current_page: int, last_page: int, per_page: int, total: int}, message: string}
             */
            return $this->successPaginated(
                ProductResource::collection($products),
                'Products retrieved successfully'
            );
        }

        /**
         * Create a product
         *
         * Admin only. Also creates the inventory row (initial `quantity`, default 0) and syncs `category_ids`.
         */
        public function store(StoreProductRequest $request): JsonResponse
        {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $quantity = $data['quantity'] ?? 0;
            $categoryIds = $data['category_ids'] ?? [];
            unset($data['quantity'], $data['category_ids']);

            $product = DB::transaction(function () use ($data, $quantity, $categoryIds) {
                $product = Product::create($data);
                $product->inventory()->create(['quantity' => $quantity]);
                $product->categories()->sync($categoryIds);

                return $product;
            });

            Cache::tags(['products'])->flush();

            /**
             * Product created.
             *
             * @status 201
             * @body array{data: ProductResource, message: string}
             */
            return $this->success(
                new ProductResource($product->load(['categories', 'inventory'])),
                'Product created successfully',
                201
            );
        }

        /**
         * Get a product
         *
         * Public. Includes categories and the available stock.
         */
        public function show(Product $product): JsonResponse
        {
            $cacheKey = "products:show:{$product->id}";

            $product = Cache::tags(['products'])->remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => $product->load(['categories', 'inventory'])
            );

            /**
             * The product.
             *
             * @body array{data: ProductResource, message: string}
             */
            return $this->success(new ProductResource($product));
        }

        /**
         * Update a product
         *
         * Admin only. Categories are only changed when `category_ids` is sent.
         */
        public function update(UpdateProductRequest $request, Product $product): JsonResponse
        {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            unset($data['category_ids']);

            DB::transaction(function () use ($request, $product, $data) {
                $product->update($data);

                if ($request->has('category_ids')) {
                    $product->categories()->sync($request->input('category_ids', []));
                }
            });

            Cache::tags(['products'])->flush();

            /**
             * Product updated.
             *
             * @body array{data: ProductResource, message: string}
             */
            return $this->success(
                new ProductResource($product->load(['categories', 'inventory'])),
                'Product updated successfully'
            );
        }

        /**
         * Delete a product
         *
         * Admin only. Soft delete; existing orders keep their price and name snapshots.
         */
        public function destroy(Product $product): JsonResponse
        {
            $product->delete();

            Cache::tags(['products'])->flush();

            /**
             * Product deleted.
             *
             * @body array{data: array{}, message: string}
             */
            return $this->success(
                message: 'Product deleted successfully',
            );
        }
    }
