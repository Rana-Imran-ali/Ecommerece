<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * Display a paginated listing of the authenticated user's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $orders = Order::where('user_id', $userId)
            ->with(['items.product.primaryImage', 'address', 'payments'])
            ->latest('id')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified order details.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        // Enforce user data isolation
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $order->load(['items.product.primaryImage', 'address', 'payments', 'couponUsages.coupon']);

        return response()->json([
            'success' => true,
            'data' => $order,
        ], Response::HTTP_OK);
    }

    /**
     * Create a new order from the user's active shopping cart (Checkout).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', 'string', 'in:cod,card,bank_transfer'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        // Security: Ensure address belongs to the authenticated user
        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Selected delivery address does not belong to your account.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Fetch user cart
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shopping cart is empty.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate stock availability for all cart items
        foreach ($cart->items as $item) {
            if (!$item->product) {
                return response()->json([
                    'success' => false,
                    'message' => "A product in your cart is no longer available.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for '{$item->product->name}'. Available: {$item->product->stock}, Requested: {$item->quantity}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Calculate Subtotal
        $subtotal = $cart->items->sum(function ($item) {
            return (float) $item->product->price * $item->quantity;
        });

        // Optional Coupon Calculation
        $coupon = null;
        $discountAmount = 0.0;

        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', trim($validated['coupon_code']))
                ->where('is_active', true)
                ->first();

            if ($coupon) {
                if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
                    return response()->json([
                        'success' => false,
                        'message' => "Coupon requires a minimum order amount of $" . number_format($coupon->min_order_amount, 2),
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $discountAmount = ($subtotal * (float) $coupon->discount_percent) / 100;
                if ($coupon->max_discount && $discountAmount > (float) $coupon->max_discount) {
                    $discountAmount = (float) $coupon->max_discount;
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $totalAmount = max(0, round($subtotal - $discountAmount, 2));

        // Execute Order Placement in Database Transaction
        $order = DB::transaction(function () use ($user, $address, $cart, $totalAmount, $validated, $coupon) {
            // 1. Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'status' => 'pending',
                'total_amount' => $totalAmount,
            ]);

            // 2. Transfer Cart Items to Order Items and Decrement Stock
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => (float) $cartItem->product->price,
                ]);

                // Lock and decrement product stock
                Product::where('id', $cartItem->product_id)
                    ->decrement('stock', $cartItem->quantity);
            }

            // 3. Record Coupon Usage if applied
            if ($coupon) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ]);
            }

            // 4. Create Payment Record
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'amount' => $totalAmount,
                'status' => $validated['payment_method'] === 'cod' ? 'pending' : 'completed',
            ]);

            // 5. Clear Cart Items
            $cart->items()->delete();

            return $order;
        });

        $order->load(['items.product.primaryImage', 'address', 'payments', 'couponUsages.coupon']);

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => $order,
        ], Response::HTTP_CREATED);
    }

    /**
     * Cancel a pending order and restore inventory stock.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        // Enforce user data isolation
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be cancelled because its current status is '{$order->status}'.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($order) {
            // Restore inventory stock for each item
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }

            // Update order and payment status
            $order->update(['status' => 'cancelled']);
            $order->payments()->update(['status' => 'cancelled']);
        });

        $order->load(['items.product.primaryImage', 'payments']);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled and product stock restored.',
            'data' => $order,
        ], Response::HTTP_OK);
    }
}
