<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Attributes\Fillable;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    #[Fillable(['user_id', 'order_id', 'used_at'])]
    class CouponUsage extends Model
    {
        use HasFactory;

        public $timestamps = false;

        public function coupon(): BelongsTo
        {
            return $this->belongsTo(Coupon::class);
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function order(): BelongsTo
        {
            return $this->belongsTo(Order::class);
        }
    }
