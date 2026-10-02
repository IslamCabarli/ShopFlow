<?php

    namespace App\Services;

    use App\Exceptions\InvalidCouponException;
    use App\Models\Coupon;
    use App\Models\User;

    class CouponService
    {
        public function findByCodeOrFail(string $code): Coupon
        {
            $coupon = Coupon::where('code', $code)->first();

            if (!$coupon) {
                throw new InvalidCouponException('Coupon code not found.');
            }

            return $coupon;
        }

        /**
         * Validate a coupon against all business rules and return the discount
         * amount. Takes whatever Coupon instance the caller passes in (locked
         * or not) — locking is the caller's responsibility, not this service's.
         */
        public function validate(Coupon $coupon, float $subtotal, User $user): float
        {
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
                ? $subtotal * ((float) $coupon->value / 100)
                : (float) $coupon->value;

            if ($coupon->maximum_discount !== null) {
                $discount = min($discount, (float) $coupon->maximum_discount);
            }

            return round(min($discount, $subtotal), 2);
        }

        public function recordUsage(Coupon $coupon, User $user, int $orderId): void
        {
            $coupon->usages()->create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'used_at' => now(),
            ]);

            $coupon->increment('used_count');
        }
    }
