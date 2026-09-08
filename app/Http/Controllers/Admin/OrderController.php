<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\InventoryLog;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

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

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $order->update(['status' => $newStatus]);

        // Auto-update inventory when order is cancelled (restore stock)
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $before = $product->stock;
                    $after  = $before + $item->quantity;
                    $product->update(['stock' => $after]);

                    InventoryLog::create([
                        'product_id'      => $product->id,
                        'user_id'         => auth()->id(),
                        'type'            => 'return',
                        'quantity'        => $item->quantity,
                        'quantity_before' => $before,
                        'quantity_after'  => $after,
                        'reference_id'    => $order->id,
                        'notes'           => "Stock returned due to order #{$order->id} cancellation",
                    ]);
                }
            }
        }

        return back()->with('success', "Order #{$order->id} status updated to \"{$newStatus}\".");
    }
}
