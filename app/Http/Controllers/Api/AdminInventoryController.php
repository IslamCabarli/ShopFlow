<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Inventory\AdjustInventoryRequest;
    use App\Http\Resources\InventoryResource;
    use App\Http\Traits\ApiResponse;
    use App\Models\Inventory;
    use App\Models\Product;
    use App\Services\InventoryService;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class AdminInventoryController extends Controller
    {
        use ApiResponse;

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

            return $this->successPaginated(
                InventoryResource::collection($inventories),
                'Inventories retrieved successfully'
            );
        }

        public function adjust(
            AdjustInventoryRequest $request,
            Product $product,
            InventoryService $inventoryService
        ): JsonResponse {
            $inventory = $inventoryService->adjust($product, $request->integer('delta'));

            return $this->success(
                new InventoryResource($inventory->load('product:id,name,sku')),
                'Inventory adjusted successfully'
            );
        }
    }
