<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Review\StoreReviewRequest;
    use App\Http\Resources\ReviewResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Product;
    use App\Models\Review;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class ReviewController extends Controller
    {
        use ApiResponse;

        public function index(Product $product): JsonResponse
        {
            $reviews = $product->reviews()
                ->with('user')
                ->latest()
                ->paginate(15);

            return $this->successPaginated(
                ReviewResource::collection($reviews),
                'Reviews retrieved successfully'
            );
        }

        public function store(StoreReviewRequest $request, Product $product): JsonResponse
        {
            $this->authorize('create', [Review::class, $product]);

            $review = $product->reviews()->create([
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ]);

            return $this->success(
                new ReviewResource($review->load('user')),
                'Review submitted successfully',
                201
            );
        }

        public function update(StoreReviewRequest $request, Review $review): JsonResponse
        {
            $this->authorize('update', $review);

            $review->update($request->validated());

            return $this->success(
                new ReviewResource($review->load('user')),
                'Review updated successfully'
            );
        }

        public function destroy(Review $review): JsonResponse
        {
            $this->authorize('delete', $review);

            $review->delete();

            return $this->success(message: 'Review deleted successfully');
        }
    }
