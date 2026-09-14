<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Filters\ProductFilter;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ProductFilter $filter): JsonResponse
    {
        $products = Product::query()
                    ->with(['categories', 'inventory']);

        $products = $filter->apply($products, $request);

        $products = $products->paginate(15);

        return $this->successPaginated(
            ProductResource::collection($products),
        'Products retrieved successfully');
    }



    /**
     * Store a newly created resource in storage.
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


        return $this->success(
            new ProductResource($product->load(['categories', 'inventory'])),
            'Product created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        return $this->success(new ProductResource($product->load(['categories', 'inventory'])));
    }


    /**
     * Update the specified resource in storage.
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

        return $this->success(
            new ProductResource($product->load(['categories', 'inventory'])),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->success(
            message:'Product deleted successfully',
        );
    }
}
