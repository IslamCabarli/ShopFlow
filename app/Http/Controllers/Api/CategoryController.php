<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Category\StoreCategoryRequest;
    use App\Http\Requests\Category\UpdateCategoryRequest;
    use App\Http\Resources\CategoryResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Category;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Str;

    class CategoryController extends Controller
    {
        use ApiResponse;

        private const CACHE_TTL = 300; // 5 minutes

        /**
         * Display a listing of the resource.
         */
        public function index(Request $request): JsonResponse
        {
            $cacheKey = 'categories:index:' . $request->query('page', 1);

            $categories = Cache::tags(['categories'])->remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => Category::paginate(15)
            );

            return $this->successPaginated(
                CategoryResource::collection($categories),
                'Categories retrieved successfully'
            );
        }

        /**
         * Store a newly created resource in storage.
         */
        public function store(StoreCategoryRequest $request): JsonResponse
        {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            $category = Category::create($data);

            Cache::tags(['categories'])->flush();

            return $this->success(
                new CategoryResource($category),
                'Category created successfully',
                201
            );
        }

        /**
         * Display the specified resource.
         */
        public function show(Category $category): JsonResponse
        {
            $cacheKey = "categories:show:{$category->id}";

            $category = Cache::tags(['categories'])->remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => $category
            );

            return $this->success(new CategoryResource($category));
        }

        /**
         * Update the specified resource in storage.
         */
        public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
        {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? $category->slug;

            $category->update($data);

            Cache::tags(['categories'])->flush();

            return $this->success(
                new CategoryResource($category),
                'Category updated successfully'
            );
        }

        /**
         * Remove the specified resource from storage.
         */
        public function destroy(Category $category): JsonResponse
        {
            $category->delete();

            Cache::tags(['categories'])->flush();

            return $this->success(
                message: 'Category deleted successfully'
            );
        }
    }
