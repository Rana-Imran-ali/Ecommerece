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
use App\Services\OrderCancellationService;
use App\Services\PaymentFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        protected PaymentFulfillmentService   $paymentFulfillmentService,
        protected OrderCancellationService    $cancellationService
    ) {
    }
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

        // ── 0. Concurrency Prevention: Atomic lock per user checkout ─────────
        $lock = Cache::lock('checkout_user_' . $user->id, 15);
        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'An order is already being processed for your account. Please wait a moment.',
            ], Response::HTTP_CONFLICT);
        }

        $paymentMethod         = $request->input('payment_method');
        $stripePaymentIntentId = $request->input('stripe_payment_intent_id');

        // ── Concurrency Prevention: Atomic lock on the specific PaymentIntent ──
        // Synchronizes client checkout directly with Stripe webhook
        $piLock = ($paymentMethod === 'card' && !empty($stripePaymentIntentId))
            ? Cache::lock('stripe_pi_' . $stripePaymentIntentId, 15)
            : null;

        if ($piLock) {
            try {
                $piLock->block(5);
            } catch (\Throwable $e) {}
        }


        // ── Webhook Race Condition Resolution ─────────────────────────────
        // If the Stripe webhook arrived first and already fulfilled this order,
        // return the created order gracefully instead of throwing 422.
            if ($paymentMethod === 'card' && !empty($stripePaymentIntentId)) {
                $existingPayment = Payment::where('stripe_payment_intent_id', $stripePaymentIntentId)->first();
                if ($existingPayment) {
                    $existingOrder = $existingPayment->order;
                    if ($existingOrder && (int) $existingOrder->user_id === (int) $user->id) {
                        $userCart = Cart::where('user_id', $user->id)->first();
                        // Replay protection: if user still has an active cart with items, reject reuse
                        if ($userCart && $userCart->items()->exists()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'This payment has already been processed for an existing order.',
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }

                        // Webhook successfully fulfilled the order and cleared the cart!
                        $existingOrder->load(['items.product.primaryImage', 'address', 'payments', 'couponUsages.coupon']);
                        return response()->json([
                            'success' => true,
                            'message' => 'Order already processed.',
                            'data'    => $existingOrder,
                        ], Response::HTTP_OK);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'This payment has already been processed for an existing order.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            // ── 1. Shared base validation ─────────────────────────────────────────
            $baseRules = [
                'address_id'     => ['required', 'integer', 'exists:addresses,id'],
                'customer_email' => ['nullable', 'email', 'max:255'],
                'payment_method' => ['required', 'string', 'in:cod,bank_transfer,card'],
                'coupon_code'    => ['nullable', 'string'],
                'expected_total' => ['nullable', 'numeric', 'min:0'],
            ];

        // ── 2. Per-method additional validation rules ─────────────────────────
        if ($paymentMethod === 'card') {
            // Card: require the confirmed Stripe PaymentIntent ID (client confirms card via Stripe.js first)
            $baseRules['stripe_payment_intent_id'] = ['required', 'string', 'starts_with:pi_'];
        } elseif ($paymentMethod === 'bank_transfer') {
            // Bank Transfer: require sender details, strict format validation, and payment proof file
            $baseRules['sender_bank']           = ['required', 'string', 'min:2', 'max:100', 'regex:/^[\pL\s\.\,\-\&]+$/u'];
            $baseRules['sender_name']           = ['required', 'string', 'min:2', 'max:150', 'regex:/^[\pL\s\.\,\'\-]+$/u'];
            $baseRules['transaction_reference'] = ['required', 'string', 'min:5', 'max:100', 'regex:/^[A-Za-z0-9\-\_]+$/'];
            $baseRules['payment_proof']         = ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120']; // max 5 MB
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

            // Store the uploaded payment proof/receipt file
            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            }

            $paymentDetails = [
                'sender_bank'         => $validated['sender_bank'],
                'sender_name'         => $validated['sender_name'],
                'proof_path'          => $proofPath,
                'proof_original_name' => $request->file('payment_proof')?->getClientOriginalName(),
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

        // ── 7b. Cart Price Drift Protection ──────────────────────────────────
        // Ensure the customer isn't charged a different price if catalog prices
        // or promotions changed while items sat in their cart or checkout screen.
        if (isset($validated['expected_total']) && $validated['expected_total'] !== null) {
            $expectedTotal = (float) $validated['expected_total'];
            if (abs($expectedTotal - $totalAmount) > 0.05) {
                return response()->json([
                    'success' => false,
                    'message' => "Price update detected: The cart total has changed from $" . number_format($expectedTotal, 2) . " to $" . number_format($totalAmount, 2) . " due to updated product pricing. Please review the updated total before confirming your order.",
                    'data'    => [
                        'expected_total' => $expectedTotal,
                        'current_total'  => $totalAmount,
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

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

        // ── 9. Execute Order Placement ────────────────────────────────────────
        //
        // CARD PAYMENTS → Delegated entirely to PaymentFulfillmentService.
        //   The service holds the distributed lock (stripe_pi_*), performs an inner
        //   idempotency check via lockForUpdate(), deducts stock atomically, and
        //   writes Order + OrderItems + Payment + InventoryLog in a single DB transaction.
        //   We NEVER auto-refund on DB exceptions — if the transaction fails the
        //   payment record was never written, so the webhook will re-attempt fulfillment.
        //
        // COD / BANK TRANSFER → Handled inline below (no Stripe involved).

        if ($paymentMethod === 'card') {
            try {
                $customerEmail = !empty($validated['customer_email']) ? trim($validated['customer_email']) : $user->email;

                $order = $this->paymentFulfillmentService->fulfill(
                    paymentIntentId: $stripePaymentIntentId,
                    sessionId:       null,
                    userId:          $user->id,
                    addressId:       $validated['address_id'],
                    customerEmail:   $customerEmail,
                    couponCode:      $validated['coupon_code'] ?? null,
                    chargedCents:    isset($intent) ? (int) $intent->amount : 0,
                    paymentDetails:  $paymentDetails
                );
            } catch (\Throwable $e) {
                // DB transaction rolled back — no payment row written.
                // Do NOT auto-refund: the Stripe webhook will retry and fulfillment
                // will succeed once the transient error is resolved.
                Log::error('Card order fulfillment failed (no auto-refund)', [
                    'intent_id' => $stripePaymentIntentId,
                    'error'     => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Order placement failed due to a server error. Your payment was captured; our team will contact you shortly.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            } finally {
                $lock->release();
                if (isset($piLock) && $piLock) {
                    try { $piLock->release(); } catch (\Throwable $e) {}
                }
            }

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order could not be placed (cart empty, address invalid, or stock unavailable). Please contact support if your card was charged.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $order->load(['items.product.primaryImage', 'address', 'payments', 'couponUsages.coupon']);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data'    => $order,
            ], Response::HTTP_CREATED);
        }

        // ── COD / Bank Transfer: inline transaction ───────────────────────────
        try {
            $order = DB::transaction(
                function () use (
                    $user, $address, $cart, $totalAmount, $validated, $coupon,
                    $paymentMethod, $transactionReference, $paymentDetails
                ) {
                    $customerEmail = !empty($validated['customer_email']) ? trim($validated['customer_email']) : $user->email;
                    $expectedDeliveryDate = now()->addDays(4)->toDateString();

                    $order = Order::create([
                        'user_id'                => $user->id,
                        'customer_email'         => $customerEmail,
                        'address_id'             => $address->id,
                        'shipping_name'          => $address->name,
                        'shipping_phone'         => $address->phone,
                        'shipping_address_line1' => $address->address_line1,
                        'shipping_address_line2' => $address->address_line2,
                        'shipping_city'          => $address->city,
                        'shipping_state'         => $address->state,
                        'shipping_postal_code'   => $address->postal_code,
                        'shipping_country'       => $address->country,
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

                            $affected = ProductVariant::where('id', $variant->id)
                                ->where('stock', '>=', $cartItem->quantity)
                                ->decrement('stock', $cartItem->quantity);

                            if (!$affected) {
                                throw new \RuntimeException("Insufficient stock for '{$cartItem->product->name} ({$variantName})'.");
                            }

                            $beforeStock = $variantBeforeStock;
                            $afterStock  = $variantAfterStock;
                        } else {
                            if (!$product || $product->stock < $cartItem->quantity) {
                                throw new \RuntimeException("Insufficient stock for '{$cartItem->product->name}'. Available: " . ($product->stock ?? 0));
                            }

                            $beforeStock = (int) $product->stock;
                            $afterStock  = $beforeStock - $cartItem->quantity;

                            $affected = Product::where('id', $product->id)
                                ->where('stock', '>=', $cartItem->quantity)
                                ->decrement('stock', $cartItem->quantity);

                            if (!$affected) {
                                throw new \RuntimeException("Insufficient stock for '{$cartItem->product->name}'.");
                            }
                        }

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
                            'quantity_before'    => $beforeStock,
                            'quantity_after'     => $afterStock,
                            'reference_id'       => (string) $order->id,
                            'notes'              => "Order #{$order->id} placed via " . strtoupper($paymentMethod) . ($variantName ? " ({$variantName})" : ''),
                        ]);
                    }

                    if ($coupon) {
                        $lockedCoupon = Coupon::where('id', $coupon->id)->lockForUpdate()->first();
                        if (!$lockedCoupon || ($err = $lockedCoupon->globalValidationError())) {
                            throw new \RuntimeException($err ?? 'Selected coupon is no longer available.');
                        }

                        $alreadyUsed = CouponUsage::where('coupon_id', $lockedCoupon->id)
                            ->where('user_id', $user->id)
                            ->exists();

                        if ($alreadyUsed) {
                            throw new \RuntimeException("You have already redeemed coupon '{$lockedCoupon->code}'.");
                        }

                        CouponUsage::create([
                            'coupon_id' => $lockedCoupon->id,
                            'user_id'   => $user->id,
                            'order_id'  => $order->id,
                        ]);
                    }

                    Payment::create([
                        'order_id'              => $order->id,
                        'payment_method'        => $paymentMethod,
                        'amount'                => $totalAmount,
                        'status'                => 'pending',
                        'transaction_reference' => $transactionReference,
                        'payment_details'       => $paymentDetails,
                    ]);

                    $cart->items()->delete();

                    return $order;
                }
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } finally {
            $lock->release();
        }

        $order->load(['items.product.primaryImage', 'address', 'payments', 'couponUsages.coupon']);

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data'    => $order,
        ], Response::HTTP_CREATED);
    }

    /**
     * Cancel a pending or processing order.
     * Delegates all business logic (refund, stock restore, idempotency) to OrderCancellationService.
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

        // Customers may only cancel pending (COD/bank) or processing (card) orders.
        // Shipped, delivered, and already-cancelled orders are non-cancellable.
        $cancellableStatuses = ['pending', 'processing'];
        if (!in_array($order->status, $cancellableStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be cancelled because its current status is '{$order->status}'. Only pending or processing orders can be cancelled.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->cancellationService->cancel(
            order:        $order,
            actorUserId:  $request->user()->id,
            actor:        'customer',
            issueRefund:  true
        );

        if ($result->failed()) {
            return response()->json([
                'success' => false,
                'message' => $result->errorMessage,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'success' => true,
            'message' => $result->message(),
            'data'    => null,
        ], Response::HTTP_OK);
    }
}
