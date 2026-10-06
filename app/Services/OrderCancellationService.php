<?php

namespace App\Services;

use App\Models\CouponUsage;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderCancellationService
 *
 * Single source of truth for ALL cancellation and refund operations.
 * Handles:
 *   - Customer self-cancellation  (OrderController::cancel)
 *   - Admin order cancellation    (Admin\OrderController::updateStatus -> cancelled)
 *   - Admin payment refund        (Admin\PaymentController::updateStatus -> refunded)
 *
 * Guarantees:
 *   Idempotency  - already-cancelled orders are silently ignored.
 *   No duplicate Stripe refunds - payment.status guard + distributed lock.
 *   Atomic DB - stock + coupon + statuses committed in one transaction.
 *   Stripe refund BEFORE DB write - never silently cancel without returning money.
 */
class OrderCancellationService
{
    /**
     * Cancel an order, optionally issuing a Stripe refund for card payments.
     *
     * @param  Order       $order         The order to cancel.
     * @param  int|null    $actorUserId   Who is performing the cancellation (for InventoryLog).
     * @param  string      $actor         'customer' | 'admin' - used in log notes.
     * @param  bool        $issueRefund   Whether to issue a Stripe refund for card payments.
     *                                   Pass false when Stripe already triggered the refund
     *                                   (e.g. via webhook) or when refund was already done.
     * @return CancellationResult
     */
    public function cancel(
        Order $order,
        ?int $actorUserId,
        string $actor = 'customer',
        bool $issueRefund = true
    ): CancellationResult {
        // 1. Idempotency guard - silently succeed if already cancelled
        if ($order->status === 'cancelled') {
            return CancellationResult::alreadyCancelled();
        }

        // 2. Load card payment (single query)
        $cardPayment = $order->payments()->where('payment_method', 'card')->first();
        $refundIssued = false;

        // 3. Stripe refund BEFORE any DB change
        //    Only if: card payment exists + PI id present + caller wants a refund
        if ($issueRefund && $cardPayment && $cardPayment->stripe_payment_intent_id) {
            $result = $this->issueStripeRefund($cardPayment);

            if (!$result['success']) {
                return CancellationResult::stripeError($result['message']);
            }

            $refundIssued = $result['refunded'];
        }

        // 4. Atomic DB transaction
        DB::transaction(function () use ($order, $actorUserId, $actor, $cardPayment) {
            // Re-check inside the transaction (prevents race between two concurrent requests)
            $fresh = Order::where('id', $order->id)->lockForUpdate()->first();
            if ($fresh->status === 'cancelled') {
                return;
            }

            // 4a. Mark order cancelled
            $fresh->update(['status' => 'cancelled']);

            // 4b. Update payment statuses
            if ($cardPayment) {
                // Only update if not already refunded (prevents overwriting 'refunded')
                Payment::where('id', $cardPayment->id)
                    ->whereNotIn('status', ['refunded'])
                    ->update(['status' => 'refunded']);
            }

            // Mark non-card payments (COD / bank_transfer) as cancelled (preserve failed / refunded)
            $order->payments()
                ->where('payment_method', '!=', 'card')
                ->whereNotIn('status', ['cancelled', 'failed', 'refunded'])
                ->update(['status' => 'cancelled']);

            // 4c. Restore coupon usage (delete the usage record to free the coupon)
            CouponUsage::where('order_id', $order->id)->delete();

            // 4d. Restore inventory stock (pessimistic row locks per item)
            foreach ($order->items as $item) {
                $this->restoreStock($item, $order->id, $actorUserId, $actor);
            }
        });

        $order->refresh();

        return CancellationResult::success($refundIssued || ($cardPayment && $cardPayment->stripe_payment_intent_id));
    }

    /**
     * Issue a Stripe refund for a given Payment record.
     * Idempotent: silently succeeds if already refunded.
     *
     * @return array{success: bool, refunded: bool, message: string}
     */
    private function issueStripeRefund(Payment $payment): array
    {
        // Guard: do not double-refund an already-refunded payment
        if ($payment->status === 'refunded') {
            Log::info("Skipping duplicate refund: payment #{$payment->id} is already refunded.");
            return ['success' => true, 'refunded' => false, 'message' => ''];
        }

        // Distributed lock prevents two concurrent requests from both calling Stripe
        $lock = Cache::lock('refund_payment_' . $payment->id, 15);

        try {
            if (!$lock->get()) {
                // Another process is already issuing the refund - treat as success
                Log::info("Refund lock busy for payment #{$payment->id}, skipping duplicate request.");
                return ['success' => true, 'refunded' => false, 'message' => ''];
            }

            // Re-check status AFTER acquiring lock (another process may have finished)
            $fresh = Payment::find($payment->id);
            if ($fresh?->status === 'refunded') {
                return ['success' => true, 'refunded' => false, 'message' => ''];
            }

            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $stripe->refunds->create([
                'payment_intent' => $payment->stripe_payment_intent_id,
            ]);

            Log::info("Stripe refund issued for payment #{$payment->id}, PI: {$payment->stripe_payment_intent_id}");
            return ['success' => true, 'refunded' => true, 'message' => ''];

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // "charge has already been refunded" is not an error - it means we are idempotent
            if (str_contains($e->getMessage(), 'already been refunded')) {
                Log::info("Stripe: payment #{$payment->id} was already refunded externally.");
                return ['success' => true, 'refunded' => false, 'message' => ''];
            }

            Log::error("Stripe refund failed for payment #{$payment->id}: " . $e->getMessage());
            return ['success' => false, 'refunded' => false, 'message' => 'Stripe refund error: ' . $e->getMessage()];

        } catch (\Exception $e) {
            Log::error("Stripe refund exception for payment #{$payment->id}: " . $e->getMessage());
            return ['success' => false, 'refunded' => false, 'message' => 'Unable to process the Stripe refund. Please try again or contact support.'];
        } finally {
            try { $lock->release(); } catch (\Throwable $e) {}
        }
    }

    /**
     * Restore inventory stock for one order item and write an InventoryLog.
     */
    private function restoreStock(
        \App\Models\OrderItem $item,
        int $orderId,
        ?int $actorUserId,
        string $actor
    ): void {
        if ($item->product_variant_id) {
            $variant = ProductVariant::where('id', $item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if ($variant) {
                $before = (int) $variant->stock;
                $after  = $before + $item->quantity;
                $variant->update(['stock' => $after]);

                // Synchronize parent product stock
                $product = Product::where('id', $item->product_id)->first();
                $product?->syncStockFromVariants();

                InventoryLog::create([
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $variant->id,
                    'user_id'            => $actorUserId,
                    'type'               => 'return',
                    'quantity'           => $item->quantity,
                    'quantity_before'    => $before,
                    'quantity_after'     => $after,
                    'reference_id'       => (string) $orderId,
                    'notes'              => "Stock restored: order #{$orderId} cancelled by {$actor}" . ($item->variant_name ? " ({$item->variant_name})" : ''),
                ]);
            }
        } else {
            $product = Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $before = (int) $product->stock;
                $after  = $before + $item->quantity;
                $product->update(['stock' => $after]);

                InventoryLog::create([
                    'product_id'         => $product->id,
                    'product_variant_id' => null,
                    'user_id'            => $actorUserId,
                    'type'               => 'return',
                    'quantity'           => $item->quantity,
                    'quantity_before'    => $before,
                    'quantity_after'     => $after,
                    'reference_id'       => (string) $orderId,
                    'notes'              => "Stock restored: order #{$orderId} cancelled by {$actor}",
                ]);
            }
        }
    }
}

/**
 * Value object returned by OrderCancellationService::cancel().
 * Carries typed success/failure state and a user-facing message.
 */
class CancellationResult
{
    private function __construct(
        public readonly bool   $success,
        public readonly bool   $refundIssued,
        public readonly bool   $alreadyCancelled,
        public readonly string $errorMessage = ''
    ) {}

    public static function success(bool $refundIssued): self
    {
        return new self(true, $refundIssued, false);
    }

    public static function alreadyCancelled(): self
    {
        return new self(true, false, true);
    }

    public static function stripeError(string $message): self
    {
        return new self(false, false, false, $message);
    }

    public function failed(): bool
    {
        return !$this->success;
    }

    /** User-facing success message. */
    public function message(): string
    {
        if ($this->alreadyCancelled) {
            return 'Order is already cancelled.';
        }

        return $this->refundIssued
            ? 'Order cancelled and a full refund has been issued to your card (typically 5-10 business days).'
            : 'Order cancelled successfully.';
    }
}
