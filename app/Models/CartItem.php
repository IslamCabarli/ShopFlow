<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'quantity'])]

class CartItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer'
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
