<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

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

        $payment->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', "Payment #{$payment->id} status updated to " . ucfirst($validated['status']) . '.');
    }
}
