<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'stock',
        'status',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Product $product) {
            if ($product->stock > 0) {
                InventoryLog::create([
                    'product_id'      => $product->id,
                    'user_id'         => auth()->id(),
                    'type'            => 'stock_in',
                    'quantity'        => (int) $product->stock,
                    'quantity_before' => 0,
                    'quantity_after'  => (int) $product->stock,
                    'notes'           => 'Initial stock on product creation',
                ]);
            }
        });
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all images for the product.
     */
    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get all customer reviews for the product.
     */
    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get all inventory movement logs for the product.
     */
    public function inventoryLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryLog::class)->latest('id');
    }

    /**
     * Get all options for the product (e.g., Size, Color).
     */
    public function options(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * Get all variants for the product.
     */
    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}

