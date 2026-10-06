<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',       // nullable: set to null if the product is force-deleted
        'product_variant_id',
        'product_name',     // snapshot at purchase time — always populated for audit
        'variant_name',
        'quantity',
        'price',
    ];

    protected $casts = [
        'product_id'         => 'integer',
        'product_variant_id' => 'integer',
        'quantity'           => 'integer',
        'price'              => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    /**
     * Returns the product name for audit/display.
     * Falls back to the product_name snapshot so cancelled order history
     * always shows correct product info even if the product was later deleted.
     */
    public function getDisplayProductNameAttribute(): string
    {
        return $this->product_name
            ?? $this->product?->name
            ?? 'Deleted Product';
    }
}
