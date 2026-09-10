<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display user's payments.
     */
    public function index()
    {
        $payments = Payment::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with('order')
            ->latest()
            ->paginate(10);

        return view('payments.index', compact('payments'));
    }

    /**
     * Display a single payment.
     */
    public function show(Payment $payment)
    {
        // Make sure payment belongs to logged-in user's order
        abort_unless(
            $payment->order->user_id === Auth::id(),
            403
        );

        $payment->load('order');

        return view('payments.show', compact('payment'));
    }
}