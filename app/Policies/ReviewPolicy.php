<?php

    namespace App\Policies;

    use App\Models\Product;
    use App\Models\Review;
    use App\Models\User;

    class ReviewPolicy
    {
        public function create(User $user, Product $product): bool
        {
            $hasPurchased = $user->orders()
                ->whereHas('orderItems', fn ($q) => $q->where('product_id', $product->id))
                ->exists();

            if (!$hasPurchased) {
                return false;
            }

            $alreadyReviewed = Review::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();

            return !$alreadyReviewed;
        }

        public function update(User $user, Review $review): bool
        {
            return $review->user_id === $user->id;
        }

        public function delete(User $user, Review $review): bool
        {
            return $review->user_id === $user->id;
        }
    }
