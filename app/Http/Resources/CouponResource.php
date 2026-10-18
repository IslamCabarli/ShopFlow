<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class CouponResource extends JsonResource
    {
        public function toArray(Request $request): array
        {
            return [
                'id' => $this->id,
                'code' => $this->code,
                'type' => $this->type,
                'value' => $this->value,
                'minimum_order_amount' => $this->minimum_order_amount,
                'maximum_discount' => $this->maximum_discount,
                'usage_limit' => $this->usage_limit,
                'per_user_limit' => $this->per_user_limit,
                'used_count' => $this->used_count,
                'starts_at' => $this->starts_at,
                'expires_at' => $this->expires_at,
                'is_active' => $this->is_active,
                'created_at' => $this->created_at,
            ];
        }
    }
