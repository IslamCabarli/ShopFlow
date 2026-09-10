<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable(['name', 'slug'])]

class Category extends Model
{
    use HasFactory, SoftDeletes;


    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }



}
