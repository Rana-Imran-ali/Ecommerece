<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
        $paymentMethod = $request->input('payment_method');

        // ── 1. Shared base validation ─────────────────────────────────────────
        $baseRules = [
            'address_id'     => ['required', 'integer', 'exists:addresses,id'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', 'string', 'in:cod,bank_transfer,card'],
            'coupon_code'    => ['nullable', 'string'],
        ];

        // ── 2. Per-method additional validation rules ─────────────────────────
        if ($paymentMethod === 'card') {
            // Card: require the confirmed Stripe PaymentIntent ID (client confirms card via Stripe.js first)
            $baseRules['stripe_payment_intent_id'] = ['required', 'string', 'starts_with:pi_'];
        } elseif ($paymentMethod === 'bank_transfer') {
            // Bank Transfer: require sender details and transaction reference
            $baseRules['sender_bank']           = ['required', 'string', 'max:100'];
            $baseRules['sender_name']           = ['required', 'string', 'max:150'];
            $baseRules['transaction_reference'] = ['required', 'string', 'max:100'];
        }

        $validated = $request->validate($baseRules);

        // ── 3. Verify address ownership ───────────────────────────────────────
        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Selected delivery address does not belong to your account.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── 4. Fetch user cart ────────────────────────────────────────────────
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product', 'items.variant.optionValues.option'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shopping cart is empty.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── 5. Pre-flight stock check ─────────────────────────────────────────
        foreach ($cart->items as $item) {
            if (!$item->product) {
                return response()->json([
                    'success' => false,
                    'message' => 'A product in your cart is no longer available.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $availableStock = $item->variant ? (int) $item->variant->stock : (int) $item->product->stock;
            $itemName = $item->variant ? "{$item->product->name} ({$item->variant->title})" : $item->product->name;

            if ($availableStock < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for '{$itemName}'. Available: {$availableStock}, Requested: {$item->quantity}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // ── 6. For card payments: verify the Stripe PaymentIntent succeeded ───
        $stripePaymentIntentId = null;
        $paymentDetails        = null;
        $transactionReference  = null;

        if ($paymentMethod === 'card') {
            $stripePaymentIntentId = $validated['stripe_payment_intent_id'];

            // 1. Prevent replay / double-spending
            if (Payment::where('stripe_payment_intent_id', $stripePaymentIntentId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment has already been processed for an existing order.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $intent = $stripe->paymentIntents->retrieve($stripePaymentIntentId, [
                    'expand' => ['latest_charge.payment_method_details'],
                ]);

                // 2. Verify payment intent ownership matches authenticated user
                if (isset($intent->metadata->user_id) && (int) $intent->metadata->user_id !== (int) $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment authorization does not belong to your account.',
                    ], Response::HTTP_FORBIDDEN);
                }

                // Ensure the PaymentIntent succeeded
                if ($intent->status !== 'succeeded') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Card payment was not completed. Please try again.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                // Capture safe, non-sensitive payment details for records
                $charge         = $intent->latest_charge ?? null;
                $cardBrand      = null;
                $cardLast4      = null;
                $cardholderName = null;

                if (is_object($charge)) {
                    $cardBrand      = $charge->payment_method_details->card->brand ?? null;
                    $cardLast4      = $charge->payment_method_details->card->last4 ?? null;
                    $cardholderName = $charge->billing_details->name ?? null;
                } elseif (isset($intent->charges->data[0])) {
                    $chargeData     = $intent->charges->data[0];
                    $cardBrand      = $chargeData->payment_method_details->card->brand ?? null;
                    $cardLast4      = $chargeData->payment_method_details->card->last4 ?? null;
                    $cardholderName = $chargeData->billing_details->name ?? null;
                }

                $transactionReference = $stripePaymentIntentId;
                $paymentDetails = [
                    'brand'            => $cardBrand,
                    'last4'            => $cardLast4,
                    'cardholder_name'  => $cardholderName,
                ];

            } catch (\Exception $e) {
                Log::error('Stripe PaymentIntent verification failed', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify payment. Please contact support if the issue persists.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } elseif ($paymentMethod === 'bank_transfer') {
            $transactionReference = $validated['transaction_reference'];
            $paymentDetails = [
                'sender_bank' => $validated['sender_bank'],
                'sender_name' => $validated['sender_name'],
            ];
        }

        // ── 7. Optional Coupon Calculation ────────────────────────────────────
        $subtotal = $cart->items->sum(fn($i) => ($i->variant ? $i->variant->effective_price : (float) $i->product->price) * $i->quantity);
        $coupon         = null;
        $discountAmount = 0.0;

        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', trim($validated['coupon_code']))->first();

            if ($coupon && ($error = $coupon->globalValidationError())) {
                return response()->json([
                    'success' => false,
                    'message' => $error,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already redeemed coupon '{$coupon->code}'. Each coupon can only be used once per customer.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coupon requires a minimum order amount of $' . number_format($coupon->min_order_amount, 2),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Supports 'percent' and 'fixed', always capped at subtotal
            $discountAmount = $coupon->calculateDiscount($subtotal);
        }

        $totalAmount = round($subtotal - $discountAmount, 2);

        // ── 8. Card payment: verify amount consistency ────────────────────────
        if ($paymentMethod === 'card' && isset($intent)) {
            $intentAmountCents  = (int) $intent->amount;
            $expectedAmountCents = (int) round($totalAmount * 100);
            // Allow ±1 cent rounding tolerance
            if (abs($intentAmountCents - $expectedAmountCents) > 1) {
                Log::warning('PaymentIntent amount mismatch', [
                    'user_id'  => $user->id,
                    'intent'   => $intentAmountCents,
                    'expected' => $expectedAmountCents,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount mismatch. Please restart the checkout.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // ── 9. Execute Order Placement in Database Transaction ────────────────
        try {
            $order = DB::transaction(
                function () use (
                    $user, $address, $cart, $totalAmount, $validated, $coupon,
                    $paymentMethod, $stripePaymentIntentId, $transactionReference, $paymentDetails
                ) {
                    $customerEmail = !empty($validated['customer_email']) ? trim($validated['customer_email']) : $user->email;
                    $expectedDeliveryDate = now()->addDays(4)->toDateString();

                    $order = Order::create([
                        'user_id'                => $user->id,
                        'customer_email'         => $customerEmail,
                        'address_id'             => $address->id,
                        'status'                 => 'pending',
                        'expected_delivery_date' => $expectedDeliveryDate,
                        'total_amount'           => $totalAmount,
                    ]);

                    foreach ($cart->items as $cartItem) {
                        $product = Product::where('id', $cartItem->product_id)->lockForUpdate()->first();

                        $variant = null;
                        $itemPrice = (float) ($product ? $product->price : 0);
                        $variantName = null;
                        $variantBeforeStock = null;
                        $variantAfterStock = null;

                        if ($cartItem->product_variant_id) {
                            $variant = ProductVariant::where('id', $cartItem->product_variant_id)->lockForUpdate()->first();
                            if (!$variant || $variant->stock < $cartItem->quantity) {
                                $varTitle = $cartItem->variant?->title ?? 'Variant';
                                throw new \RuntimeException("Insufficient stock for '{$cartItem->product->name} ({$varTitle})'. Available: " . ($variant->stock ?? 0));
                            }
                            $itemPrice = $variant->effective_price;
                            $variantName = $variant->title;

                            $variantBeforeStock = (int) $variant->stock;
                            $variantAfterStock  = $variantBeforeStock - $cartItem->quantity;
                            $variant->update(['stock' => $variantAfterStock]);
                        } else {
                            if (!$product || $product->stock < $cartItem->quantity) {
                                throw new \RuntimeException("Insufficient stock for '{$cartItem->product->name}'. Available: " . ($product->stock ?? 0));
                            }
                        }

                        $beforeStock = (int) $product->stock;
                        $afterStock  = max(0, $beforeStock - $cartItem->quantity);
                        $product->update(['stock' => $afterStock]);

                        OrderItem::create([
                            'order_id'           => $order->id,
                            'product_id'         => $cartItem->product_id,
                            'product_variant_id' => $variant?->id,
                            'product_name'       => $product->name,
                            'variant_name'       => $variantName,
                            'quantity'           => $cartItem->quantity,
                            'price'              => $itemPrice,
                        ]);

                        InventoryLog::create([
                            'product_id'         => $product->id,
                            'product_variant_id' => $variant?->id,
                            'user_id'            => $user->id,
                            'type'               => 'sale',
                            'quantity'           => -$cartItem->quantity,
                            'quantity_before'    => $variant ? $variantBeforeStock : $beforeStock,
                            'quantity_after'     => $variant ? $variantAfterStock : $afterStock,
                            'reference_id'       => (string) $order->id,
                            'notes'              => "Order #{$order->id} placed via " . strtoupper($paymentMethod) . ($variantName ? " ({$variantName})" : ''),
                        ]);
                    }

                    if ($coupon) {
                        CouponUsage::create([
                            'coupon_id' => $coupon->id,
                            'user_id'   => $user->id,
                            'order_id'  => $order->id,
                        ]);
                    }

                    // Determine payment status based on method
                    $paymentStatus = $paymentMethod === 'card' ? 'completed' : 'pending';

                    Payment::create([
                        'order_id'                => $order->id,
                        'payment_method'          => $paymentMethod,
                        'amount'                  => $totalAmount,
                        'status'                  => $paymentStatus,
                        'stripe_payment_intent_id'=> $stripePaymentIntentId,
                        'transaction_reference'   => $transactionReference,
                        'payment_details'         => $paymentDetails,
                    ]);

                    // Card orders: mark order as processing immediately
                    if ($paymentMethod === 'card') {
                        $order->update(['status' => 'processing']);
                    }

                    $cart->items()->delete();

                    return $order;
                }
            );
        } catch (\RuntimeException $e) {
            // If card payment already succeeded but order placement failed (e.g. out of stock),
            // trigger an automatic refund immediately to prevent customer fund loss!
            if ($paymentMethod === 'card' && !empty($stripePaymentIntentId)) {
                try {
                    $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                    $stripe->refunds->create([
                        'payment_intent' => $stripePaymentIntentId,
                    ]);
                    Log::info("Automatic refund issued for PaymentIntent {$stripePaymentIntentId} due to order placement failure: " . $e->getMessage());
                } catch (\Exception $refEx) {
                    Log::error("Failed to auto-refund PaymentIntent {$stripePaymentIntentId}", ['error' => $refEx->getMessage()]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Your order could not be completed because an item became unavailable. Your card payment was automatically refunded.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->load(['items.product.primaryImage', 'address', 'payments', 'couponUsages.coupon']);

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data'    => $order,
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

        DB::transaction(function () use ($order, $request) {
            // Restore coupon usage if order is cancelled
            CouponUsage::where('order_id', $order->id)->delete();

            // Restore inventory stock for each item with pessimistic lock
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
                        'user_id'            => $request->user()->id,
                        'type'               => 'return',
                        'quantity'           => $item->quantity,
                        'quantity_before'    => $variant ? $variantBefore : $before,
                        'quantity_after'     => $variant ? $variantAfter : $after,
                        'reference_id'       => (string) $order->id,
                        'notes'              => "Stock restored due to customer order #{$order->id} cancellation" . ($item->variant_name ? " ({$item->variant_name})" : ''),
                    ]);
                }
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
