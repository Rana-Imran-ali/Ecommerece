<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\OrderStatusNotification;
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
    }
}
