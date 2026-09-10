<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $cart = $user->cart()
            ->with('items.product')
            ->first();

        $addresses = $user->addresses()->get();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $total = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return view('checkout.index', compact(
            'cart',
            'addresses',
            'total'
        ));
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string|in:cod,online',
        ]);

        $user = Auth::user();

        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $cart = $user->cart()
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $total = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $order = DB::transaction(function () use (
            $user,
            $address,
            $cart,
            $total,
            $validated
        ) {
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'total_amount' => $total,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
            ]);

            $cart->items()->delete();

            return $order;
        });

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order placed successfully.');
    }
}