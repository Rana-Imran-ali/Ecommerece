<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;

    /**
     * The notification type:
     *   'status'        — generic order status update (used for auto-send on creation)
     *   'approval'      — admin manually approved/confirmed the order
     *   'delivery_date' — admin manually sent expected delivery date
     */
    public string $type;

    /**
     * Create a new notification instance.
     *
     * @param Order  $order
     * @param string $type  'status' | 'approval' | 'delivery_date'
     */
    public function __construct(Order $order, string $type = 'status')
    {
        $this->order = $order->loadMissing(['items.product', 'address', 'user']);
        $this->type  = $type;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $orderId        = $this->order->id;
        $status         = strtolower($this->order->status);
        $customerName   = $this->order->address?->name ?: ($this->order->user?->name ?: 'Valued Customer');
        $recipientEmail = $this->order->recipient_email ?: ($notifiable->email ?? 'customer@example.com');
        $deliveryDate   = $this->order->expected_delivery_formatted;
        $orderUrl       = url('/orders/' . $orderId);

        [$subject, $headline, $statusMessage, $statusLabel] = $this->resolveContent(
            $orderId, $status, $deliveryDate
        );

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.orders.status', [
                'order'                => $this->order,
                'customerName'         => $customerName,
                'recipientEmail'       => $recipientEmail,
                'subject'              => $subject,
                'headline'             => $headline,
                'statusMessage'        => $statusMessage,
                'statusLabel'          => $statusLabel,
                'expectedDeliveryDate' => $deliveryDate,
                'orderUrl'             => $orderUrl,
            ]);
    }

    /**
     * Resolve email content based on notification type and order status.
     */
    protected function resolveContent(int $orderId, string $status, ?string $deliveryDate): array
    {
        // ── Approval email (manually triggered by admin) ────────────────────
        if ($this->type === 'approval') {
            return [
                "Your Order #{$orderId} Has Been Approved!",
                "Order #{$orderId} Confirmed ✓",
                "Great news! Your order has been reviewed and approved by our team. We are now preparing your package for dispatch.",
                "Approved",
            ];
        }

        // ── Delivery Date email (manually triggered by admin) ───────────────
        if ($this->type === 'delivery_date') {
            $dateStr = $deliveryDate ?? 'to be confirmed';
            return [
                "Delivery Date Scheduled for Your Order #{$orderId}",
                "📦 Your Order is on Its Way!",
                "Your order has been scheduled for delivery. Your estimated delivery date is {$dateStr}. Our courier partner will deliver it to your address.",
                "Out for Delivery",
            ];
        }

        // ── Generic status-based email (auto-sent on order creation) ────────
        return match ($status) {
            'pending' => [
                "Order Confirmation: #{$orderId} Received",
                "Order #{$orderId} Received",
                "Thank you for your order! We have received it and it is currently pending confirmation by our team.",
                "Order Received",
            ],
            'processing' => [
                "Order Confirmed & Processing: #{$orderId}",
                "Order #{$orderId} is Confirmed!",
                "Great news! Your order has been confirmed and our team is preparing your package.",
                "Confirmed & Processing",
            ],
            'out_for_delivery', 'shipped' => [
                "Out for Delivery: Your Order #{$orderId} is on the Way!",
                "Order #{$orderId} is Out for Delivery",
                "Your package has been handed to our courier partner and is on its way to you.",
                "Out for Delivery",
            ],
            'delivered' => [
                "Delivered: Order #{$orderId} has Arrived!",
                "Order #{$orderId} Delivered",
                "Your package has been successfully delivered. We hope you love your purchase!",
                "Delivered",
            ],
            'cancelled' => [
                "Order Cancellation: #{$orderId}",
                "Order #{$orderId} has been Cancelled",
                "Your order #{$orderId} has been cancelled. Any applicable refunds or stock restorations have been processed.",
                "Cancelled",
            ],
            default => [
                "Order Status Update: #{$orderId} is " . ucfirst($status),
                "Order #{$orderId} Update",
                "The status of your order #{$orderId} has been updated to " . ucfirst($status) . ".",
                ucfirst($status),
            ],
        };
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id'               => $this->order->id,
            'type'                   => $this->type,
            'status'                 => $this->order->status,
            'total_amount'           => $this->order->total_amount,
            'expected_delivery_date' => $this->order->expected_delivery_date,
            'recipient_email'        => $this->order->recipient_email,
        ];
    }
}

