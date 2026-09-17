<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'customer_email',
        'address_id',
        'shipping_name',
        'shipping_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'status',
        'expected_delivery_date',
        'total_amount',
    ];

    protected $casts = [
        'total_amount'           => 'decimal:2',
        'expected_delivery_date' => 'date',
    ];

    /**
     * Formatted shipping address string from snapshot fields or relation.
     */
    public function getShippingAddressFormattedAttribute(): string
    {
        if ($this->shipping_address_line1) {
            $parts = array_filter([
                $this->shipping_name,
                $this->shipping_phone ? "Tel: {$this->shipping_phone}" : null,
                $this->shipping_address_line1 . ($this->shipping_address_line2 ? ", {$this->shipping_address_line2}" : ''),
                $this->shipping_city . ($this->shipping_state ? ", {$this->shipping_state}" : '') . ($this->shipping_postal_code ? " {$this->shipping_postal_code}" : ''),
                $this->shipping_country,
            ]);
            return implode("\n", $parts);
        }

        if ($this->address) {
            $addr = $this->address;
            $parts = array_filter([
                $addr->name,
                $addr->phone ? "Tel: {$addr->phone}" : null,
                $addr->street,
                $addr->city . ($addr->state ? ", {$addr->state}" : '') . ($addr->postal_code ? " {$addr->postal_code}" : ''),
                $addr->country,
            ]);
            return implode("\n", $parts);
        }

        return 'Shipping address not recorded';
    }

    /**
     * Get the email where notifications should be routed.
     * Prefers order-specific customer_email with fallback to registered user's email.
     */
    public function getRecipientEmailAttribute(): ?string
    {
        return $this->customer_email ?: $this->user?->email;
    }

    /**
     * Human-friendly formatted expected delivery date.
     */
    public function getExpectedDeliveryFormattedAttribute(): ?string
    {
        if (!$this->expected_delivery_date) {
            return null;
        }

        return Carbon::parse($this->expected_delivery_date)->format('l, M d, Y');
    }

    /**
     * Standard human-friendly label for order statuses.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'          => 'Pending Confirmation',
            'processing'       => 'Confirmed & Processing',
            'out_for_delivery' => 'Out for Delivery',
            'shipped'          => 'Out for Delivery',
            'delivered'        => 'Delivered',
            'cancelled'        => 'Cancelled',
            default            => ucfirst($this->status),
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }
}
