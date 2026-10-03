<?php

    namespace App\Observers;

    use App\Models\Review;
    use Illuminate\Support\Facades\Cache;

    class ReviewObserver
    {
        public function created(Review $review): void
        {
            $this->recalculate($review);
        }

        public function updated(Review $review): void
        {
            $this->recalculate($review);
        }

        public function deleted(Review $review): void
        {
            $this->recalculate($review);
        }

        private function recalculate(Review $review): void
        {
            $product = $review->product;

            $product->average_rating = $product->reviews()->avg('rating') ?? 0;
            $product->reviews_count = $product->reviews()->count();
            $product->save();

            Cache::tags(['products'])->flush();
        }
    }
