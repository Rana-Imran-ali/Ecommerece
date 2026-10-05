<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Services\OrderCancellationService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const STATUSES = ['pending', 'processing', 'out_for_delivery', 'shipped', 'delivered', 'cancelled'];

    public function __construct(
        protected OrderCancellationService $cancellationService
    ) {}

    public function index(Request $request)
    {
        $query = Order::with(['user', 'items'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $cleanId = ltrim(preg_replace('/[^0-9]/', '', $search), '0');

            $query->where(function ($q) use ($search, $cleanId) {
                if ($cleanId !== '') {
                    $q->where('id', (int) $cleanId);
                }
                $q->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('shipping_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('address', function ($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        $orders   = $query->paginate(20)->withQueryString();
        $statuses = self::STATUSES;
        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'address', 'items.product.primaryImage', 'payments', 'couponUsages.coupon']);
        $statuses = self::STATUSES;
        return view('admin.orders.show', compact('order', 'statuses'));
    }

    /**
     * Update order status.
     * When transitioning TO 'cancelled', delegates to OrderCancellationService
     * which handles stock restoration, coupon cleanup, payment status, and
     * Stripe refund — all idempotently and without duplicating business logic.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status'                  => 'required|in:' . implode(',', self::STATUSES),
            'expected_delivery_date'  => 'nullable|date',
        ]);

        $newStatus = $validated['status'];

        // ── Non-cancellation transitions: straightforward status update ──────
        if ($newStatus !== 'cancelled') {
            $updateData = ['status' => $newStatus];
            if ($request->has('expected_delivery_date')) {
                $updateData['expected_delivery_date'] = $validated['expected_delivery_date'] ?: null;
            }
            $order->update($updateData);

            return back()->with('success', "Order #{$order->id} status updated to \"{$newStatus}\". Use the Email Controls below to notify the customer.");
        }

        // ── Cancellation transition: delegate to service ─────────────────────
        // Admin cancellations: issue a Stripe refund for card-paid orders.
        if ($order->status === 'cancelled') {
            return back()->with('success', "Order #{$order->id} is already cancelled.");
        }

        $result = $this->cancellationService->cancel(
            order:       $order,
            actorUserId: auth()->id(),
            actor:       'admin',
            issueRefund: true
        );

        if ($result->failed()) {
            return back()->with('error', "Could not cancel Order #{$order->id}: " . $result->errorMessage);
        }

        // Apply expected_delivery_date update if provided alongside cancellation
        if ($request->has('expected_delivery_date')) {
            $order->update(['expected_delivery_date' => $validated['expected_delivery_date'] ?: null]);
        }

        return back()->with('success', "Order #{$order->id} has been cancelled. " . $result->message());
    }

    public function sendApprovalEmail(Order $order)
    {
        if ($order->status === 'cancelled') {
            return back()->with('error', "Cannot send approval email for a cancelled order.");
        }

        $recipientEmail = $order->recipient_email;
        if (empty($recipientEmail)) {
            return back()->with('error', "No recipient email found for Order #{$order->id}.");
        }

        try {
            (new OrderObserver())->dispatchNotification($order, 'approval', 'admin_manual_approval', true);
            return back()->with('success', "✅ Approval email sent to {$recipientEmail} for Order #{$order->id}.");
        } catch (\Throwable $e) {
            return back()->with('error', "Could not send approval email to {$recipientEmail}: " . $e->getMessage());
        }
    }

    public function sendDeliveryDateEmail(Request $request, Order $order)
    {
        $validated = $request->validate([
            'expected_delivery_date' => 'required|date|after_or_equal:today',
        ], [
            'expected_delivery_date.required'        => 'Please enter an expected delivery date.',
            'expected_delivery_date.after_or_equal'  => 'The delivery date must be today or a future date.',
        ]);

        $recipientEmail = $order->recipient_email;
        if (empty($recipientEmail)) {
            return back()->with('error', "No recipient email found for Order #{$order->id}.");
        }

        $order->update(['expected_delivery_date' => $validated['expected_delivery_date']]);
        $order->refresh();

        try {
            (new OrderObserver())->dispatchNotification($order, 'delivery_date', 'admin_manual_delivery_date', true);
            $formattedDate = $order->expected_delivery_formatted ?? $validated['expected_delivery_date'];
            return back()->with('success', "📦 Delivery date email sent to {$recipientEmail}. Expected: {$formattedDate}.");
        } catch (\Throwable $e) {
            return back()->with('error', "Could not send delivery date email to {$recipientEmail}: " . $e->getMessage());
        }
    }
}
