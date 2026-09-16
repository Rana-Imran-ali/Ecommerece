<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductOptionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_option_id',
        'value',
    ];

    /**
     * Get the option that owns this value.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    /**
     * Get the variants associated with this option value.
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_values',
            'product_option_value_id',
            'product_variant_id'
        )->withTimestamps();
    }
}
