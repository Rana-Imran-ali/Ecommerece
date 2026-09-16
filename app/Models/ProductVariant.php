<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Get the parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the option values that define this variant (e.g., Red, XL).
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_values',
            'product_variant_id',
            'product_option_value_id'
        )->withTimestamps();
    }

    /**
     * Get cart items associated with this variant.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get order items associated with this variant.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get inventory movement logs for this variant.
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Accessor for effective price: returns variant price if set, otherwise product price.
     */
    public function getEffectivePriceAttribute(): float
    {
        if ($this->price !== null) {
            return (float) $this->price;
        }

        return $this->product ? (float) $this->product->price : 0.00;
    }

    /**
     * Generate a readable options title for the variant (e.g. "Color: Red / Size: L").
     */
    public function getTitleAttribute(): string
    {
        if ($this->relationLoaded('optionValues')) {
            return $this->optionValues->map(function ($val) {
                $optionName = $val->relationLoaded('option') && $val->option ? $val->option->name . ': ' : '';
                return $optionName . $val->value;
            })->implode(' / ');
        }

        return (string) ($this->sku ?? "Variant #{$this->id}");
    }
}
