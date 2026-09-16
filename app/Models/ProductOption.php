<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
    ];

    /**
     * Get the product that owns this option.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the values for this product option.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
