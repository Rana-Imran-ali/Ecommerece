<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the full URL to the image.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->image ? '/storage/' . ltrim($this->image, '/') : '';
    }

    /**
     * Get the product that owns the image.
     */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
