<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentFulfillmentService
{
    /**
     * Fulfill a payment and create the corresponding Order atomically.
     * Uses a distributed lock on stripe_pi_{paymentIntentId} to prevent race conditions
     * between /payment/success redirect, Stripe webhooks, and client checkouts.
     *
     * @param string      $paymentIntentId
     * @param string|null $sessionId
     * @param int|null    $userId
     * @param int|null    $addressId
     * @param string|null $customerEmail
     * @param string|null $couponCode
     * @param int         $chargedCents
     * @param array|null  $paymentDetails
     * @return Order|null
     * @throws \Throwable
     */
    public function fulfill(
        string $paymentIntentId,
        ?string $sessionId = null,
        ?int $userId = null,
        ?int $addressId = null,
        ?string $customerEmail = null,
        ?string $couponCode = null,
        int $chargedCents = 0,
        ?array $paymentDetails = null
    ): ?Order {
        // 1. Centralized concurrency lock across all fulfillment entry points
        $lock = Cache::lock('stripe_pi_' . $paymentIntentId, 20);

        try {
            $lock->block(10);
        } catch (\Throwable $e) {
            Log::warning("Could not acquire lock for stripe_pi_{$paymentIntentId}: " . $e->getMessage());
        }

        try {
            // 2. Idempotency Check: Did another thread (webhook, success redirect, or API) already fulfill this payment?
            $existingPayment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->when($sessionId, fn($q) => $q->orWhere('stripe_session_id', $sessionId))
                ->first();

            if ($existingPayment) {
                // If payment already exists, ensure status is completed and order is processing
                if ($existingPayment->status !== 'completed' || ($sessionId && empty($existingPayment->stripe_session_id))) {
                    DB::transaction(function () use ($existingPayment, $sessionId) {
                        $existingPayment->update([
                            'status' => 'completed',
                            'stripe_session_id' => $existingPayment->stripe_session_id ?: $sessionId,
                        ]);
                        $existingPayment->order?->update(['status' => 'processing']);
                    });
                }

                Log::info("Payment {$paymentIntentId} is already recorded for order #{$existingPayment->order_id}. Returning existing order.");
                return $existingPayment->order;
            }

            // 3. Validate user and address
            if (!$userId || !$addressId) {
                Log::warning("Payment fulfillment skipped for {$paymentIntentId}: missing userId ({$userId}) or addressId ({$addressId}).");
                return null;
            }

            $address = Address::where('id', $addressId)->where('user_id', $userId)->first();
            $cart    = Cart::where('user_id', $userId)->with(['items.product', 'items.variant.optionValues.option', 'user'])->first();

            if (!$address || !$cart || $cart->items->isEmpty()) {
                Log::warning("Payment fulfillment skipped for {$paymentIntentId}: cart empty or address not found.");
                return null;
            }

            // 4. Pre-check stock availability
            foreach ($cart->items as $cartItem) {
                if (!$cartItem->product) {
                    Log::warning("Payment fulfillment failed for {$paymentIntentId}: product {$cartItem->product_id} not found.");
                    return null;
                }

                $availableStock = $cartItem->variant ? (int) $cartItem->variant->stock : (int) $cartItem->product->stock;
                if ($availableStock < $cartItem->quantity) {
                    $itemTitle = $cartItem->variant ? "{$cartItem->product->name} ({$cartItem->variant->title})" : $cartItem->product->name;
                    Log::warning("Payment fulfillment failed for {$paymentIntentId}: insufficient stock for {$itemTitle}. Available: {$availableStock}, Requested: {$cartItem->quantity}.");
                    return null;
                }
            }

            // 5. Calculate totals and coupon
            $subtotal = $cart->items->sum(fn($i) => ($i->variant ? $i->variant->effective_price : (float) $i->product->price) * $i->quantity);
            $coupon = null;
            $discountAmount = 0.0;

            if (!empty($couponCode)) {
                $coupon = Coupon::where('code', trim($couponCode))->first();
                if ($coupon && !$coupon->globalValidationError()) {
                    $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $userId)->exists();
                    if (!$alreadyUsed && (!$coupon->min_order_amount || $subtotal >= (float) $coupon->min_order_amount)) {
                        $discountAmount = $coupon->calculateDiscount($subtotal);
                    } else {
                        $coupon = null;
                    }
                } else {
                    $coupon = null;
                }
            }

            $totalAmount = round($subtotal - $discountAmount, 2);
            $expectedCents = (int) round($totalAmount * 100);

            if ($chargedCents > 0 && abs($chargedCents - $expectedCents) > 1) {
                Log::warning("Payment fulfillment amount mismatch for {$paymentIntentId}: charged {$chargedCents} cents, expected {$expectedCents} cents.");
                return null;
            }

            // 6. Execute atomic database transaction
            // CRITICAL: We NEVER auto-refund on database exceptions!
            return DB::transaction(function () use (
                $userId, $customerEmail, $address, $cart, $totalAmount, $coupon,
                $paymentIntentId, $sessionId, $paymentDetails
            ) {
                // Secondary check inside pessimistic row lock to prevent race conditions
                $existingPayment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                    ->lockForUpdate()
                    ->first();

                if ($existingPayment) {
                    return $existingPayment->order;
                }

                $expectedDeliveryDate = now()->addDays(4)->toDateString();

                $order = Order::create([
                    'user_id'                => $userId,
                    'customer_email'         => $customerEmail ?: ($cart->user?->email ?? ''),
                    'address_id'             => $address->id,
                    'shipping_name'          => $address->name,
                    'shipping_phone'         => $address->phone,
                    'shipping_address_line1' => $address->address_line1,
                    'shipping_address_line2' => $address->address_line2,
                    'shipping_city'          => $address->city,
                    'shipping_state'         => $address->state,
                    'shipping_postal_code'   => $address->postal_code,
                    'shipping_country'       => $address->country,
                    'status'                 => 'processing',
                    'expected_delivery_date' => $expectedDeliveryDate,
                    'total_amount'           => $totalAmount,
                ]);

                foreach ($cart->items as $cartItem) {
                    $product = Product::where('id', $cartItem->product_id)->lockForUpdate()->first();
                    $variant = null;
                    $itemPrice = (float) ($product ? $product->price : 0);
                    $variantName = null;
                    $variantBeforeStock = null;
                    $variantAfterStock = null;

                    if ($cartItem->product_variant_id) {
                        $variant = ProductVariant::where('id', $cartItem->product_variant_id)->lockForUpdate()->first();
                        if (!$variant || $variant->stock < $cartItem->quantity) {
                            $varTitle = $cartItem->variant?->title ?? 'Variant';
                            throw new \RuntimeException("Insufficient stock for '{$product->name} ({$varTitle})'. Available: " . ($variant->stock ?? 0));
                        }

                        $itemPrice = $variant->effective_price;
                        $variantName = $variant->title;
                        $variantBeforeStock = (int) $variant->stock;
                        $variantAfterStock  = $variantBeforeStock - $cartItem->quantity;

                        $affected = ProductVariant::where('id', $variant->id)
                            ->where('stock', '>=', $cartItem->quantity)
                            ->decrement('stock', $cartItem->quantity);

                        if (!$affected) {
                            throw new \RuntimeException("Insufficient stock for '{$product->name} ({$variantName})'.");
                        }

                        $beforeStock = $variantBeforeStock;
                        $afterStock  = $variantAfterStock;
                    } else {
                        if (!$product || $product->stock < $cartItem->quantity) {
                            throw new \RuntimeException("Insufficient stock for '{$product->name}'. Available: " . ($product->stock ?? 0));
                        }

                        $beforeStock = (int) $product->stock;
                        $afterStock  = $beforeStock - $cartItem->quantity;

                        $affected = Product::where('id', $product->id)
                            ->where('stock', '>=', $cartItem->quantity)
                            ->decrement('stock', $cartItem->quantity);

                        if (!$affected) {
                            throw new \RuntimeException("Insufficient stock for '{$product->name}'.");
                        }
                    }

                    OrderItem::create([
                        'order_id'           => $order->id,
                        'product_id'         => $cartItem->product_id,
                        'product_variant_id' => $variant?->id,
                        'product_name'       => $product->name,
                        'variant_name'       => $variantName,
                        'quantity'           => $cartItem->quantity,
                        'price'              => $itemPrice,
                    ]);

                    InventoryLog::create([
                        'product_id'         => $product->id,
                        'product_variant_id' => $variant?->id,
                        'user_id'            => $userId,
                        'type'               => 'sale',
                        'quantity'           => -$cartItem->quantity,
                        'quantity_before'    => $variant ? $variantBeforeStock : $beforeStock,
                        'quantity_after'     => $variant ? $variantAfterStock : $afterStock,
                        'reference_id'       => (string) $order->id,
                        'notes'              => "Order #{$order->id} placed via Stripe" . ($variantName ? " ({$variantName})" : ''),
                    ]);
                }

                if ($coupon) {
                    $lockedCoupon = Coupon::where('id', $coupon->id)->lockForUpdate()->first();
                    if (!$lockedCoupon || ($err = $lockedCoupon->globalValidationError())) {
                        throw new \RuntimeException($err ?? 'Selected coupon is no longer available.');
                    }

                    $alreadyUsed = CouponUsage::where('coupon_id', $lockedCoupon->id)
                        ->where('user_id', $userId)
                        ->exists();

                    if ($alreadyUsed) {
                        throw new \RuntimeException("You have already redeemed coupon '{$lockedCoupon->code}'.");
                    }

                    CouponUsage::create([
                        'coupon_id' => $lockedCoupon->id,
                        'user_id'   => $userId,
                        'order_id'  => $order->id,
                    ]);
                }

                Payment::create([
                    'order_id'                 => $order->id,
                    'payment_method'           => 'card',
                    'amount'                   => $totalAmount,
                    'status'                   => 'completed',
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_session_id'        => $sessionId,
                    'transaction_reference'    => $paymentIntentId,
                    'payment_details'          => $paymentDetails,
                ]);

                $cart->items()->delete();

                return $order;
            });

        } catch (\Throwable $e) {
            Log::error("Payment fulfillment failed for PaymentIntent {$paymentIntentId}: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
            // NOTE: Do NOT auto-refund on database exceptions!
            throw $e;
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {}
        }
    }
}
