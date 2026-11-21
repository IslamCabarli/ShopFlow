<?php

    namespace App\Http\Controllers\Api;

    use App\Exceptions\CouponInUseException;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Coupon\StoreCouponRequest;
    use App\Http\Requests\Coupon\UpdateCouponRequest;
    use App\Http\Resources\CouponResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Coupon;
    use Dedoc\Scramble\Attributes\QueryParameter;
    use Dedoc\Scramble\Attributes\Response;
    use Illuminate\Http\JsonResponse;

    class AdminCouponController extends Controller
    {
        use ApiResponse;

        /**
         * List coupons
         *
         * Admin only. Newest first, 15 per page.
         */
        #[QueryParameter('page', 'Page number.', type: 'int', default: 1)]
        public function index(): JsonResponse
        {
            $coupons = Coupon::orderByDesc('id')->paginate(15);

            /**
             * A page of coupons.
             *
             * @body array{data: CouponResource[], meta: array{current_page: int, last_page: int, per_page: int, total: int}, message: string}
             */
            return $this->successPaginated(
                CouponResource::collection($coupons),
                'Coupons retrieved successfully'
            );
        }

        /**
         * Create a coupon
         *
         * Admin only. The code is normalized to upper case.
         */
        public function store(StoreCouponRequest $request): JsonResponse
        {
            $coupon = Coupon::create($request->validated());

            /**
             * Coupon created.
             *
             * @status 201
             * @body array{data: CouponResource, message: string}
             */
            return $this->success(
                new CouponResource($coupon->fresh()),
                'Coupon created successfully',
                201
            );
        }

        /**
         * Get a coupon
         *
         * Admin only.
         */
        public function show(Coupon $coupon): JsonResponse
        {
            /**
             * The coupon.
             *
             * @body array{data: CouponResource, message: string}
             */
            return $this->success(new CouponResource($coupon));
        }

        /**
         * Update a coupon
         *
         * Admin only. Partial update; changing `type` requires sending `value` again.
         */
        public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
        {
            $coupon->update($request->validated());

            /**
             * Coupon updated.
             *
             * @body array{data: CouponResource, message: string}
             */
            return $this->success(
                new CouponResource($coupon->fresh()),
                'Coupon updated successfully'
            );
        }

        /**
         * Delete a coupon
         *
         * Admin only. A coupon that was already used cannot be deleted; deactivate it instead.
         */
        #[Response(409, 'The coupon has already been used.', type: 'array{message: string, errors: array{}}')]
        public function destroy(Coupon $coupon): JsonResponse
        {
            if ($coupon->usages()->exists()) {
                throw new CouponInUseException(
                    'This coupon has already been used and cannot be deleted. Deactivate it instead.'
                );
            }

            $coupon->delete();

            /**
             * Coupon deleted.
             *
             * @body array{data: array{}, message: string}
             */
            return $this->success(message: 'Coupon deleted successfully');
        }
    }
