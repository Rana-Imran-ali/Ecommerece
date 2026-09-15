<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const STATUSES = ['pending', 'processing', 'out_for_delivery', 'shipped', 'delivered', 'cancelled'];

    public function index(Request $request)
    {
        $query = Order::with(['user', 'items'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%'));
        }
        $orders = $query->paginate(20)->withQueryString();
        $statuses = self::STATUSES;
        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'address', 'items.product.primaryImage', 'payments', 'couponUsages.coupon']);
        $statuses = self::STATUSES;
        return view('admin.orders.show', compact('order', 'statuses'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', self::STATUSES),
            'expected_delivery_date' => 'nullable|date',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];
        $updateData = ['status' => $newStatus];

        if ($request->has('expected_delivery_date')) {
            $updateData['expected_delivery_date'] = $validated['expected_delivery_date'] ?: null;
        }

        $order->update($updateData);

        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $order->payments()->where('status', 'pending')->update(['status' => 'cancelled']);

            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $before = $product->stock;
                    $after = $before + $item->quantity;
                    $product->update(['stock' => $after]);
                    InventoryLog::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'return',
                        'quantity' => $item->quantity,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'reference_id' => $order->id,
                        'notes' => "Stock returned due to order #{$order->id} cancellation",
                    ]);
                }
            }
        }

        return back()->with('success', "Order #{$order->id} status updated to \"{$newStatus}\". Use the Email Controls below to notify the customer.");
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

        (new OrderObserver())->dispatchNotification($order, 'approval', 'admin_manual_approval');

        return back()->with('success', "✅ Approval email sent to {$recipientEmail} for Order #{$order->id}.");
    }

    public function sendDeliveryDateEmail(Request $request, Order $order)
    {
        $validated = $request->validate([
            'expected_delivery_date' => 'required|date|after_or_equal:today',
        ], [
            'expected_delivery_date.required' => 'Please enter an expected delivery date.',
            'expected_delivery_date.after_or_equal' => 'The delivery date must be today or a future date.',
        ]);

        $recipientEmail = $order->recipient_email;
        if (empty($recipientEmail)) {
            return back()->with('error', "No recipient email found for Order #{$order->id}.");
        }

        $order->update(['expected_delivery_date' => $validated['expected_delivery_date']]);
        $order->refresh();

        (new OrderObserver())->dispatchNotification($order, 'delivery_date', 'admin_manual_delivery_date');

        $formattedDate = $order->expected_delivery_formatted ?? $validated['expected_delivery_date'];
        return back()->with('success', "📦 Delivery date email sent to {$recipientEmail}. Expected: {$formattedDate}.");
    }
}
