<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Models\InventoryLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['order.user'])->latest();

        // Search by Order ID, Transaction/Payment ID, Customer Name or Email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('order_id', $search)
                  ->orWhereHas('order.user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Payment Method
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        // Summary metrics
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $completedCount = Payment::where('status', 'completed')->count();
        $pendingCount = Payment::where('status', 'pending')->count();
        $failedCount = Payment::whereIn('status', ['failed', 'refunded'])->count();

        $payments = $query->paginate(20)->withQueryString();

        $statuses = ['pending', 'completed', 'failed', 'refunded'];
        $methods = Payment::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();

        return view('admin.payments.index', compact(
            'payments',
            'totalRevenue',
            'completedCount',
            'pendingCount',
            'failedCount',
            'statuses',
            'methods'
        ));
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'order.user',
            'order.items.product',
            'order.address',
        ]);

        return view('admin.payments.show', compact('payment'));
    }

    public function updateStatus(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $newStatus = $validated['status'];

        // If card payment is being marked as refunded, trigger Stripe API refund
        if ($newStatus === 'refunded' && $payment->payment_method === 'card' && !empty($payment->stripe_payment_intent_id)) {
            $secret = config('services.stripe.secret');
            if (!empty($secret)) {
                try {
                    $stripe = new StripeClient($secret);
                    $stripe->refunds->create([
                        'payment_intent' => $payment->stripe_payment_intent_id,
                    ]);
                    Log::info("Admin triggered Stripe refund for payment #{$payment->id}, intent {$payment->stripe_payment_intent_id}");
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    if (!str_contains($e->getMessage(), 'already been refunded')) {
                        Log::error("Stripe refund error for payment #{$payment->id}: " . $e->getMessage());
                        return back()->with('error', 'Stripe refund error: ' . $e->getMessage());
                    }
                } catch (\Exception $e) {
                    Log::error("Stripe refund failed for payment #{$payment->id}: " . $e->getMessage());
                    return back()->with('error', 'Failed to issue Stripe refund: ' . $e->getMessage());
                }
            }
        }

        DB::transaction(function () use ($payment, $newStatus) {
            $payment->update([
                'status' => $newStatus,
            ]);

            $order = $payment->order;
            if (!$order) {
                return;
            }

            if ($newStatus === 'completed') {
                if (!in_array($order->status, ['processing', 'out_for_delivery', 'shipped', 'delivered'])) {
                    $order->update(['status' => 'processing']);
                }
            } elseif (in_array($newStatus, ['failed', 'refunded'])) {
                if ($order->status !== 'cancelled') {
                    $order->update(['status' => 'cancelled']);

                    // Restore coupon usage
                    CouponUsage::where('order_id', $order->id)->delete();

                    // Restore inventory stock and record inventory logs
                    foreach ($order->items as $item) {
                        $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                        $variant = null;
                        $variantBefore = null;
                        $variantAfter = null;

                        if ($item->product_variant_id) {
                            $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                            if ($variant) {
                                $variantBefore = (int) $variant->stock;
                                $variantAfter  = $variantBefore + $item->quantity;
                                $variant->update(['stock' => $variantAfter]);
                            }
                        }

                        if ($product) {
                            $before = (int) $product->stock;
                            $after  = $before + $item->quantity;
                            $product->update(['stock' => $after]);

                            InventoryLog::create([
                                'product_id'         => $product->id,
                                'product_variant_id' => $variant?->id,
                                'user_id'            => auth()->id() ?? $order->user_id,
                                'type'               => 'return',
                                'quantity'           => $item->quantity,
                                'quantity_before'    => $variant ? $variantBefore : $before,
                                'quantity_after'     => $variant ? $variantAfter : $after,
                                'reference_id'       => (string) $order->id,
                                'notes'              => "Stock restored due to payment {$newStatus} for order #{$order->id}" . ($item->variant_name ? " ({$item->variant_name})" : ''),
                            ]);
                        }
                    }
                }
            }
        });

        return back()->with('success', "Payment #{$payment->id} status updated to " . ucfirst($validated['status']) . '.');
    }
}
