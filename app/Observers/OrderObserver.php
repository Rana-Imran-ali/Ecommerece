<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\OrderStatusNotification;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     * Automatically sends a confirmation email to the customer after the transaction commits
     * and all order items are persisted.
     */
    public function created(Order $order): void
    {
        DB::afterCommit(function () use ($order) {
            $order->refresh();
            $order->load(['items.product', 'address', 'user']);
            $this->dispatchNotification($order, 'status', 'order_created');
        });
    }

    /**
     * Handle the Order "updated" event.
     * NOTE: Status changes do NOT auto-send emails.
     * Emails are sent manually by the admin via the admin panel.
     */
    public function updated(Order $order): void
    {
        // Intentionally a no-op — emails are now admin-controlled.
    }

    /**
     * Safely dispatch a notification to the order's recipient email.
     * Also sends a WhatsApp message if credentials are configured and a phone number is available.
     * Made public so the AdminOrderController can call it for manual sends.
     *
     * @param Order  $order
     * @param string $type    'status' | 'approval' | 'delivery_date'
     * @param string $trigger A label for log context
     */
    public function dispatchNotification(Order $order, string $type, string $trigger): void
    {
        $recipientEmail = $order->recipient_email;

        if (empty($recipientEmail)) {
            Log::warning("Order #{$order->id} notification skipped: no recipient email found.", [
                'order_id' => $order->id,
                'trigger'  => $trigger,
            ]);
            return;
        }

        // ── Email notification ────────────────────────────────────────────
        try {
            Notification::route('mail', $recipientEmail)
                ->notify(new OrderStatusNotification($order, $type));

            Log::info("Order #{$order->id} [{$order->status}] {$type} notification dispatched to {$recipientEmail} (trigger: {$trigger})", [
                'order_id'        => $order->id,
                'type'            => $type,
                'status'          => $order->status,
                'recipient_email' => $recipientEmail,
                'expected_date'   => $order->expected_delivery_date,
            ]);
        } catch (\Throwable $e) {
            Log::error("Order #{$order->id} {$type} notification issue to {$recipientEmail}: " . $e->getMessage(), [
                'order_id'        => $order->id,
                'type'            => $type,
                'status'          => $order->status,
                'recipient_email' => $recipientEmail,
                'exception'       => get_class($e),
            ]);
        }

        // ── WhatsApp notification (optional, non-blocking) ────────────────
        $this->dispatchWhatsAppNotification($order, $type);
    }

    /**
     * Send a WhatsApp message to the customer's phone number if:
     *  1. WhatsApp credentials (access_token + phone_number_id) are configured.
     *  2. A phone number is found on the order's shipping snapshot or address relation.
     *
     * Failures are logged but do NOT affect the email flow.
     */
    protected function dispatchWhatsAppNotification(Order $order, string $type): void
    {
        // Only attempt if credentials are configured
        if (empty(config('whatsapp.access_token')) || empty(config('whatsapp.phone_number_id'))) {
            return;
        }

        // Resolve phone number: prefer shipping snapshot, fall back to address relation
        $phone = $order->shipping_phone
            ?? $order->address?->phone
            ?? null;

        if (empty($phone)) {
            return;
        }

        // Strip non-numeric characters (WhatsApp needs digits + country code, e.g. 923001234567)
        $phone = preg_replace('/\D/', '', $phone);

        $statusLabel = $order->status_label ?? ucfirst($order->status);
        $message     = match ($type) {
            'approval'      => "✅ Hi! Your order #{$order->id} has been confirmed and is now being processed. We'll update you when it's on its way.",
            'delivery_date' => "📦 Your order #{$order->id} is on its way! Expected delivery: {$order->expected_delivery_formatted}. Thank you for shopping with us!",
            default         => "🛍️ Your order #{$order->id} status has been updated to: {$statusLabel}. Thank you for shopping with us!",
        };

        try {
            app(WhatsAppService::class)->sendTextMessage($phone, $message);
            Log::info("Order #{$order->id} WhatsApp notification sent to {$phone} (type: {$type})");
        } catch (\Throwable $e) {
            Log::warning("Order #{$order->id} WhatsApp notification failed: " . $e->getMessage(), [
                'order_id' => $order->id,
                'phone'    => $phone,
                'type'     => $type,
            ]);
        }
    }
}
