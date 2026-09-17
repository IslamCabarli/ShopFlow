<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class OrderResource extends JsonResource
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
                'order_number' => $this->order_number,
                'status' => $this->status,
                'payment_status' => $this->payment_status,

                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'total' => $this->total,

                'shipping' => [
                    'name' => $this->shipping_name,
                    'address' => $this->shipping_address,
                    'city' => $this->shipping_city,
                    'country' => $this->shipping_country,
                    'postal_code' => $this->shipping_postal_code,
                ],

                'items' => OrderItemResource::collection(
                    $this->whenLoaded('orderItems')
                ),

                'payments' => PaymentResource::collection(
                    $this->whenLoaded('payments')
                ),

                'created_at' => $this->created_at,
            ];
        }
    }
