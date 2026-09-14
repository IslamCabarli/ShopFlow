<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'status' => $this->status,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'stock' => $this->whenLoaded('inventory', fn() => $this->inventory->quantity - $this->inventory->reserved_quantity),
        ];
    }
}
