<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Inventory\AdjustInventoryRequest;
    use App\Http\Resources\InventoryResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Inventory;
    use App\Models\Product;
    use App\Services\InventoryService;
    use Dedoc\Scramble\Attributes\QueryParameter;
    use Dedoc\Scramble\Attributes\Response;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class AdminInventoryController extends Controller
    {
        use ApiResponse;

        /**
         * List inventories
         *
         * Admin only. Sorted by quantity ascending, so the lowest stock comes first.
         */
        #[QueryParameter('page', 'Page number.', type: 'int', default: 1)]
        #[QueryParameter('low_stock', 'Only inventories with quantity at or below this threshold.', type: 'int')]
        public function index(Request $request): JsonResponse
        {
            $request->validate(['low_stock' => ['nullable', 'integer', 'min:0']]);

            $inventories = Inventory::with('product:id,name,sku')
                ->when(
                    $request->filled('low_stock'),
                    fn ($query) => $query->where('quantity', '<=', (int) $request->input('low_stock'))
                )
                ->orderBy('quantity')
                ->paginate(15);

            /**
             * A page of inventories.
             *
             * @body array{data: InventoryResource[], meta: array{current_page: int, last_page: int, per_page: int, total: int}, message: string}
             */
            return $this->successPaginated(
                InventoryResource::collection($inventories),
                'Inventories retrieved successfully'
            );
        }

        /**
         * Adjust a product's stock
         *
         * Admin only. `delta` is relative (positive restocks, negative writes off) so it cannot overwrite a concurrent checkout.
         */
        #[Response(409, 'The adjustment would push stock below zero or below the reserved quantity.', type: 'array{message: string, errors: array{}}')]
        public function adjust(
            AdjustInventoryRequest $request,
            Product $product,
            InventoryService $inventoryService
        ): JsonResponse {
            $inventory = $inventoryService->adjust($product, $request->integer('delta'));

            /**
             * Inventory adjusted.
             *
             * @body array{data: InventoryResource, message: string}
             */
            return $this->success(
                new InventoryResource($inventory->load('product:id,name,sku')),
                'Inventory adjusted successfully'
            );
        }
    }
