<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',       // 'percent' | 'fixed'
        'discount_percent',    // used when discount_type = 'percent'
        'discount_amount',     // used when discount_type = 'fixed'
        'max_discount',        // optional ceiling for percent discounts
        'min_order_amount',
        'max_uses',            // null = unlimited global uses
        'expires_at',
        'is_active',
    ];

    protected $attributes = [
        'discount_type'    => 'percent',
        'discount_percent' => 0.00,
        'is_active'        => true,
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'max_discount'     => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_uses'         => 'integer',
        'expires_at'       => 'datetime',
        'is_active'        => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    /**
     * True if the coupon is currently usable (active, not expired, not exhausted).
     */
    public function isCurrentlyValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && Carbon::now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->usages()->count() >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Return the validation error message if the coupon is not valid,
     * or null if it passes all global checks.
     */
    public function globalValidationError(): ?string
    {
        if (!$this->is_active) {
            return 'Coupon code is invalid or inactive.';
        }

        if ($this->expires_at !== null && Carbon::now()->gt($this->expires_at)) {
            return "Coupon '{$this->code}' has expired.";
        }

        if ($this->max_uses !== null && $this->usages()->count() >= $this->max_uses) {
            return "Coupon '{$this->code}' has reached its maximum usage limit.";
        }

        return null;
    }

    /**
     * Calculate the discount for a given subtotal.
     * The result is always capped at the subtotal so the final total never goes negative.
     *
     * @return float  Positive discount value (e.g. 10.00 = $10 off)
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->discount_type === 'fixed') {
            $discount = (float) $this->discount_amount;
        } else {
            // percent
            $discount = ($subtotal * (float) $this->discount_percent) / 100;

            // Apply optional ceiling for percent discounts
            if ($this->max_discount !== null && $discount > (float) $this->max_discount) {
                $discount = (float) $this->max_discount;
            }
        }

        // Never discount more than the subtotal
        return round(min($discount, $subtotal), 2);
    }
}
