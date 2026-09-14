<?php

    namespace App\Http\Filters;

    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Http\Request;

    class ProductFilter
    {
        public function apply(Builder $query, Request $request): Builder
        {
            return $query
                ->when(
                    $request->filled('category'),
                    fn($q) => $q->whereHas(
                        'categories',
                        fn($cq) => $cq->where('categories.id', $request->category)
                    )
                )
                ->when(
                    $request->filled('min_price'),
                    fn($q) => $q->where('price', '>=', $request->min_price)
                )
                ->when(
                    $request->filled('max_price'),
                    fn($q) => $q->where('price', '<=', $request->max_price)
                )
                ->when(
                    $request->filled('search'),
                    fn($q) => $q->where('name', 'ilike', '%' . $request->search . '%')
                )
                ->when(
                    $request->filled('sort'),
                    fn($q) => $this->applySort($q, $request->sort)
                );
        }

        protected function applySort(Builder $query, string $sort): Builder
        {
            return match ($sort) {
                'price_asc' => $query->orderBy('price', 'asc'),
                'price_desc' => $query->orderBy('price', 'desc'),
                'newest' => $query->orderBy('created_at', 'desc'),
                'oldest' => $query->orderBy('created_at', 'asc'),
                default => $query,
            };
        }
    }
