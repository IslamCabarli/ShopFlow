<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Review\StoreReviewRequest;
    use App\Http\Resources\ReviewResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Product;
    use App\Models\Review;
    use Dedoc\Scramble\Attributes\QueryParameter;
    use Illuminate\Http\JsonResponse;

    class ReviewController extends Controller
    {
        use ApiResponse;

        /**
         * List a product's reviews
         *
         * Newest first, 15 per page.
         */
        #[QueryParameter('page', 'Page number.', type: 'int', default: 1)]
        public function index(Product $product): JsonResponse
        {
            $reviews = $product->reviews()
                ->with('user')
                ->latest()
                ->paginate(15);

            /**
             * A page of reviews.
             *
             * @body array{data: ReviewResource[], meta: array{current_page: int, last_page: int, per_page: int, total: int}, message: string}
             */
            return $this->successPaginated(
                ReviewResource::collection($reviews),
                'Reviews retrieved successfully'
            );
        }

        /**
         * Review a product
         *
         * Only customers who bought the product, and only once per product (otherwise 403). Updates the product's average rating.
         */
        public function store(StoreReviewRequest $request, Product $product): JsonResponse
        {
            $this->authorize('create', [Review::class, $product]);

            $review = $product->reviews()->make($request->validated());
            $review->user_id = $request->user()->id;
            $review->save();

            /**
             * Review submitted.
             *
             * @status 201
             * @body array{data: ReviewResource, message: string}
             */
            return $this->success(
                new ReviewResource($review->load('user')),
                'Review submitted successfully',
                201
            );
        }

        /**
         * Edit my review
         */
        public function update(StoreReviewRequest $request, Review $review): JsonResponse
        {
            $this->authorize('update', $review);

            $review->update($request->validated());

            /**
             * Review updated.
             *
             * @body array{data: ReviewResource, message: string}
             */
            return $this->success(
                new ReviewResource($review->load('user')),
                'Review updated successfully'
            );
        }

        /**
         * Delete my review
         */
        public function destroy(Review $review): JsonResponse
        {
            $this->authorize('delete', $review);

            $review->delete();

            /**
             * Review deleted.
             *
             * @body array{data: array{}, message: string}
             */
            return $this->success(message: 'Review deleted successfully');
        }
    }
