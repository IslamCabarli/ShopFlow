<?php

    namespace App\Http\Controllers\Api;

    use App\Exceptions\CouponInUseException;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Coupon\StoreCouponRequest;
    use App\Http\Requests\Coupon\UpdateCouponRequest;
    use App\Http\Resources\CouponResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Coupon;
    use Illuminate\Http\JsonResponse;

    class AdminCouponController extends Controller
    {
        use ApiResponse;

        public function index(): JsonResponse
        {
            $coupons = Coupon::orderByDesc('id')->paginate(15);

            return $this->successPaginated(
                CouponResource::collection($coupons),
                'Coupons retrieved successfully'
            );
        }

        public function store(StoreCouponRequest $request): JsonResponse
        {
            $coupon = Coupon::create($request->validated());

            return $this->success(
                new CouponResource($coupon->fresh()),
                'Coupon created successfully',
                201
            );
        }

        public function show(Coupon $coupon): JsonResponse
        {
            return $this->success(new CouponResource($coupon));
        }

        public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
        {
            $coupon->update($request->validated());

            return $this->success(
                new CouponResource($coupon->fresh()),
                'Coupon updated successfully'
            );
        }

        public function destroy(Coupon $coupon): JsonResponse
        {
            if ($coupon->usages()->exists()) {
                throw new CouponInUseException(
                    'This coupon has already been used and cannot be deleted. Deactivate it instead.'
                );
            }

            $coupon->delete();

            return $this->success(message: 'Coupon deleted successfully');
        }
    }
