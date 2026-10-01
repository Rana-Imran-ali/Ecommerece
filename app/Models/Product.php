<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
                $actorId = auth()->id(); // null if called from seeder/console/job context
                InventoryLog::create([
                    'product_id'      => $product->id,
                    'user_id'         => $actorId, // column allows null for system-generated records
                    'type'            => 'stock_in',
                    'quantity'        => (int) $product->stock,
                    'quantity_before' => 0,
                    'quantity_after'  => (int) $product->stock,
                    'notes'           => $actorId
                        ? 'Initial stock on product creation'
                        : 'Initial stock on product creation (system/seeder)',
                ]);
            }
        });

        static::deleting(function (Product $product) {
            if ($product->isForceDeleting()) {
                // Hard delete: remove all image files from disk and purge DB records
                foreach ($product->images as $image) {
                    if ($image->image) {
                        Storage::disk('public')->delete($image->image);
                    }
                    $image->delete();
                }
                $product->variants()->forceDelete();
                $product->options()->forceDelete();
                // Remove all cart items referencing this product permanently
                $product->cartItems()->delete();
                $product->wishlistItems()->delete();
            } else {
                // Soft delete: cascade soft-delete to variants
                $product->variants()->delete();
                // Remove cart items so the cart doesn't silently hold unavailable products
                $product->cartItems()->delete();
                // Wishlist items: WishlistController already filters via whereHas, but clean up anyway
                $product->wishlistItems()->delete();
            }
        });

        static::restoring(function (Product $product) {
            $product->variants()->withTrashed()->restore();
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

    /**
     * Get all order items containing this product.
     */
    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all cart items containing this product.
     */
    public function cartItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get all wishlist items containing this product.
     */
    public function wishlistItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get only approved reviews for the product (for public display).
     */
    public function approvedReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }
}

