<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected OrderCancellationService $cancellationService
    ) {}

    public function index(Request $request)
    {
        $query = Payment::with(['order.user'])->latest();

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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        $totalRevenue   = Payment::where('status', 'completed')->sum('amount');
        $completedCount = Payment::where('status', 'completed')->count();
        $pendingCount   = Payment::where('status', 'pending')->count();
        $failedCount    = Payment::whereIn('status', ['failed', 'refunded'])->count();

        $payments = $query->paginate(20)->withQueryString();
        $statuses = ['pending', 'completed', 'failed', 'refunded'];
        $methods  = Payment::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();

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

    /**
     * Update payment status.
     *
     * - completed   → mark order as processing (no refund logic)
     * - refunded    → issue Stripe refund (if card) and cancel the linked order
     *                 via OrderCancellationService (idempotent, duplicate-refund-safe)
     * - failed      → cancel the order without issuing a Stripe refund
     *                 (payment already failed on Stripe's side)
     */
    public function updateStatus(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $newStatus = $validated['status'];

        // ── Guard: prevent re-processing an already-refunded payment ──────────
        if ($payment->status === 'refunded' && $newStatus === 'refunded') {
            return back()->with('error', "Payment #{$payment->id} has already been refunded.");
        }

        // ── completed: mark payment + ensure order is in processing ───────────
        if ($newStatus === 'completed') {
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => 'completed']);

                $order = $payment->order;
                if ($order && !in_array($order->status, ['processing', 'out_for_delivery', 'shipped', 'delivered'], true)) {
                    $order->update(['status' => 'processing']);
                }
            });

            return back()->with('success', "Payment #{$payment->id} marked as completed.");
        }

        // ── refunded: issue Stripe refund + cancel order via service ──────────
        if ($newStatus === 'refunded') {
            $order = $payment->order;
            if (!$order) {
                // No linked order — just mark the payment as refunded directly
                $payment->update(['status' => 'refunded']);
                return back()->with('success', "Payment #{$payment->id} marked as refunded (no linked order found).");
            }

            // Delegate to service: handles Stripe call + idempotency + stock restore
            $result = $this->cancellationService->cancel(
                order:       $order,
                actorUserId: auth()->id(),
                actor:       'admin',
                issueRefund: true    // service will skip if already refunded
            );

            if ($result->failed()) {
                return back()->with('error', "Refund failed for Payment #{$payment->id}: " . $result->errorMessage);
            }

            return back()->with('success', "Payment #{$payment->id} refunded and Order #{$order->id} cancelled. " . $result->message());
        }

        // ── failed: cancel order WITHOUT issuing a Stripe refund ──────────────
        // (Stripe already declined the charge — no money was captured)
        if ($newStatus === 'failed') {
            $payment->update(['status' => 'failed']);

            $order = $payment->order;
            if ($order) {
                $result = $this->cancellationService->cancel(
                    order:       $order,
                    actorUserId: auth()->id(),
                    actor:       'admin',
                    issueRefund: false   // no Stripe refund for a failed payment
                );

                if ($result->failed()) {
                    Log::warning("Could not cancel order #{$order->id} after payment #{$payment->id} marked failed: " . $result->errorMessage);
                }
            }

            return back()->with('success', "Payment #{$payment->id} marked as failed.");
        }

        // ── pending: simple status update (admin re-opening a payment) ────────
        $payment->update(['status' => $newStatus]);
        return back()->with('success', "Payment #{$payment->id} status updated to " . ucfirst($newStatus) . '.');
    }
}
