<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Category\StoreCategoryRequest;
    use App\Http\Requests\Category\UpdateCategoryRequest;
    use App\Http\Resources\CategoryResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Category;
    use Dedoc\Scramble\Attributes\QueryParameter;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Str;

    class CategoryController extends Controller
    {
        use ApiResponse;

        private const CACHE_TTL = 300; // 5 minutes

        /**
         * List categories
         *
         * Public. Paginated (15 per page) and cached for 5 minutes.
         */
        #[QueryParameter('page', 'Page number.', type: 'int', default: 1)]
        public function index(Request $request): JsonResponse
        {
            $cacheKey = 'categories:index:' . $request->query('page', 1);

            $categories = Cache::tags(['categories'])->remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => Category::paginate(15)
            );

            /**
             * A page of categories.
             *
             * @body array{data: CategoryResource[], meta: array{current_page: int, last_page: int, per_page: int, total: int}, message: string}
             */
            return $this->successPaginated(
                CategoryResource::collection($categories),
                'Categories retrieved successfully'
            );
        }

        /**
         * Create a category
         *
         * Admin only. The slug is generated from the name when omitted.
         */
        public function store(StoreCategoryRequest $request): JsonResponse
        {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            $category = Category::create($data);

            Cache::tags(['categories'])->flush();

            /**
             * Category created.
             *
             * @status 201
             * @body array{data: CategoryResource, message: string}
             */
            return $this->success(
                new CategoryResource($category),
                'Category created successfully',
                201
            );
        }

        /**
         * Get a category
         */
        public function show(Category $category): JsonResponse
        {
            $cacheKey = "categories:show:{$category->id}";

            $category = Cache::tags(['categories'])->remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => $category
            );

            /**
             * The category.
             *
             * @body array{data: CategoryResource, message: string}
             */
            return $this->success(new CategoryResource($category));
        }

        /**
         * Update a category
         *
         * Admin only.
         */
        public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
        {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? $category->slug;

            $category->update($data);

            Cache::tags(['categories'])->flush();

            /**
             * Category updated.
             *
             * @body array{data: CategoryResource, message: string}
             */
            return $this->success(
                new CategoryResource($category),
                'Category updated successfully'
            );
        }

        /**
         * Delete a category
         *
         * Admin only. Soft delete.
         */
        public function destroy(Category $category): JsonResponse
        {
            $category->delete();

            Cache::tags(['categories'])->flush();

            /**
             * Category deleted.
             *
             * @body array{data: array{}, message: string}
             */
            return $this->success(
                message: 'Category deleted successfully'
            );
        }
    }
