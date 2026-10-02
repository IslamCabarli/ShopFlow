<?php

    namespace App\Services;

    use App\Exceptions\InvalidCouponException;
    use App\Models\Coupon;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;

    class CouponService
    {

        public function validateAndCalculateDiscount(string $code, float $subtotal, User $user): array
        {
            $coupon = Coupon::where('code', $code)->first();

            if (!$coupon) {
                throw new InvalidCouponException('Coupon code not found.');
            }

            if (!$coupon->is_active) {
                throw new InvalidCouponException('This coupon is no longer active.');
            }

            if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
                throw new InvalidCouponException('This coupon is not active yet.');
            }

            if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
                throw new InvalidCouponException('This coupon has expired.');
            }

            if ($subtotal < $coupon->minimum_order_amount) {
                throw new InvalidCouponException(
                    "This coupon requires a minimum order of {$coupon->minimum_order_amount}."
                );
            }

            if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                throw new InvalidCouponException('This coupon has reached its usage limit.');
            }

            if ($coupon->per_user_limit !== null) {
                $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();

                if ($userUsageCount >= $coupon->per_user_limit) {
                    throw new InvalidCouponException('You have already used this coupon the maximum number of times.');
                }
            }

            $discount = $coupon->type === 'percentage'
                ? $subtotal * ($coupon->value / 100)
                : $coupon->value;

            if ($coupon->maximum_discount !== null) {
                $discount = min($discount, $coupon->maximum_discount);
            }

            // Discount can never exceed the subtotal itself.
            $discount = min($discount, $subtotal);

            return [$coupon, round($discount, 2)];
        }

        public function recordUsage(Coupon $coupon, User $user, int $orderId): void
        {
            $coupon->couponUsages()->create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'used_at' => now(),
            ]);

            $coupon->increment('used_count');
        }
    }
