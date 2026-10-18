<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class InventoryResource extends JsonResource
    {
        public function toArray(Request $request): array
        {
            return [
                'product_id' => $this->product_id,
                'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
                'sku' => $this->whenLoaded('product', fn () => $this->product?->sku),
                'quantity' => $this->quantity,
                'reserved_quantity' => $this->reserved_quantity,
                'available' => $this->quantity - $this->reserved_quantity,
                'updated_at' => $this->updated_at,
            ];
        }
    }
