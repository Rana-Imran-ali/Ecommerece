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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeController extends Controller
{
    private StripeClient $stripe;

    public function __construct(?StripeClient $stripe = null)
    {
        $this->stripe = $stripe ?? (app()->bound(StripeClient::class)
            ? app(StripeClient::class)
            : new StripeClient(config('services.stripe.secret')));
    }


    // ─────────────────────────────────────────────────────────────────────────
    // API: Create a Stripe PaymentIntent (for direct card checkout)
    // POST /api/stripe/payment-intent
    // Protected by ApiAuthMiddleware
    // ─────────────────────────────────────────────────────────────────────────
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'address_id'     => ['required', 'integer', 'exists:addresses,id'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'coupon_code'    => ['nullable', 'string'],
        ]);

        // Validate address ownership
        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Selected delivery address does not belong to your account.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Load cart
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product', 'items.variant.optionValues.option'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shopping cart is empty.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Pre-flight stock check
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

        // Calculate subtotal
        $subtotal = $cart->items->sum(fn($i) => ($i->variant ? $i->variant->effective_price : (float) $i->product->price) * $i->quantity);

        // Optional coupon discount
        $discountAmount = 0.0;

        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', trim($validated['coupon_code']))->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check active + expiry + max_uses
            if ($error = $coupon->globalValidationError()) {
                return response()->json([
                    'success' => false,
                    'message' => $error,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already redeemed coupon '{$coupon->code}'.",
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
        $amountCents = (int) round($totalAmount * 100);

        if ($amountCents < 50) { // Stripe minimum is $0.50
            return response()->json([
                'success' => false,
                'message' => 'Order total is below the minimum payment amount.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount'   => $amountCents,
                'currency' => 'usd',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id'        => (string) $user->id,
                    'address_id'     => (string) $address->id,
                    'customer_email' => !empty($validated['customer_email']) ? trim($validated['customer_email']) : (string) $user->email,
                    'coupon_code'    => $validated['coupon_code'] ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to initialize payment. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success'       => true,
            'client_secret' => $intent->client_secret,
            'intent_id'     => $intent->id,
            'amount'        => $totalAmount,
        ], Response::HTTP_OK);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: Create a Stripe Checkout Session
    // POST /api/stripe/create-session
    // Protected by ApiAuthMiddleware
    // ─────────────────────────────────────────────────────────────────────────
    public function createSession(Request $request): JsonResponse
    {
        $user = $request->user();


        $validated = $request->validate([
            'address_id'     => ['required', 'integer', 'exists:addresses,id'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'coupon_code'    => ['nullable', 'string'],
        ]);

        // ── 1. Validate address ownership ──────────────────────────────────
        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Selected delivery address does not belong to your account.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── 2. Load cart ───────────────────────────────────────────────────
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product', 'items.variant.optionValues.option'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shopping cart is empty.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── 3. Validate stock ──────────────────────────────────────────────
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

        // ── 4. Calculate totals ────────────────────────────────────────────
        $subtotal = $cart->items->sum(fn ($item) => ($item->variant ? $item->variant->effective_price : (float) $item->product->price) * $item->quantity);

        $coupon         = null;
        $discountAmount = 0.0;

        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', trim($validated['coupon_code']))->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check active + expiry + max_uses
            if ($error = $coupon->globalValidationError()) {
                return response()->json([
                    'success' => false,
                    'message' => $error,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Enforce single-use restriction per customer
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

        // ── 5. Build Stripe line items with strictly positive unit amounts ──
        $targetCents = (int) round($totalAmount * 100);
        $discountRatio = $subtotal > 0 ? ($totalAmount / $subtotal) : 1.0;
        $lineItems = [];
        $accumulatedCents = 0;
        $itemsCount = $cart->items->count();

        foreach ($cart->items as $index => $item) {
            if ($index === $itemsCount - 1) {
                $itemTotalCents = max(1, $targetCents - $accumulatedCents);
                $unitCents = $item->quantity > 0 ? max(1, (int) round($itemTotalCents / $item->quantity)) : 1;
            } else {
                $discountedPrice = (float) $item->product->price * $discountRatio;
                $unitCents = max(1, (int) round($discountedPrice * 100));
                $accumulatedCents += ($unitCents * $item->quantity);
            }

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $unitCents,
                    'product_data' => [
                        'name' => $item->product->name . ($coupon ? " (Promo {$coupon->code})" : ''),
                    ],
                ],
                'quantity' => $item->quantity,
            ];
        }

        // ── 6. Create Stripe Checkout Session (without premature stock or cart wiping) ──
        // Stock deduction, order creation, and cart wiping are safely deferred until
        // the customer actually completes payment on Stripe.
        $customerEmail = !empty($validated['customer_email']) ? trim($validated['customer_email']) : (string) $user->email;

        try {
            $sessionParams = [
                'payment_method_types' => ['card'],
                'line_items'           => $lineItems,
                'mode'                 => 'payment',
                'success_url'          => url('/payment/success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => url('/payment/cancel'),
                'expires_at'           => time() + 1800, // 30-minute auto-expiry for abandoned sessions
                'payment_intent_data'  => [
                    'metadata' => [
                        'user_id'        => (string) $user->id,
                        'address_id'     => (string) $address->id,
                        'customer_email' => $customerEmail,
                        'coupon_code'    => $coupon ? $coupon->code : '',
                    ],
                ],
                'metadata'             => [
                    'user_id'        => (string) $user->id,
                    'address_id'     => (string) $address->id,
                    'customer_email' => $customerEmail,
                    'coupon_code'    => $coupon ? $coupon->code : '',
                ],
                'customer_email'       => $customerEmail,
            ];

            $session = $this->stripe->checkout->sessions->create($sessionParams);
        } catch (\Exception $e) {
            Log::error('Stripe session creation failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initiate payment. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Stripe Checkout Session created.',
            'session_url' => $session->url,
            'session_id'  => $session->id,
        ], Response::HTTP_CREATED);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEB: Stripe Success Landing Page
    // GET /payment/success?session_id=cs_xxx
    // ─────────────────────────────────────────────────────────────────────────
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id', '');
        $orderId   = null;

        if ($sessionId) {
            try {
                $payment = Payment::where('stripe_session_id', $sessionId)->first();
                if ($payment) {
                    $orderId = $payment->order_id;
                } else {
                    $session = $this->stripe->checkout->sessions->retrieve($sessionId, [
                        'expand' => ['payment_intent'],
                    ]);

                    if ($session && $session->payment_status === 'paid') {
                        $paymentIntentId = is_object($session->payment_intent) ? $session->payment_intent->id : $session->payment_intent;
                        $userId        = isset($session->metadata->user_id) ? (int) $session->metadata->user_id : null;
                        $addressId     = isset($session->metadata->address_id) ? (int) $session->metadata->address_id : null;
                        $customerEmail = $session->metadata->customer_email ?? null;
                        $couponCode    = $session->metadata->coupon_code ?? null;

                        if ($userId && $addressId && $paymentIntentId) {
                            $order = $this->fulfillOrderFromStripe(
                                $userId,
                                $addressId,
                                $customerEmail,
                                $couponCode,
                                $paymentIntentId,
                                (int) $session->amount_total,
                                $sessionId
                            );
                            if ($order) {
                                $orderId = $order->id;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Stripe success page: could not retrieve or fulfill session', [
                    'session_id' => $sessionId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return view('payment.success', [
            'sessionId' => $sessionId,
            'orderId'   => $orderId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEB: Stripe Cancel Landing Page
    // GET /payment/cancel
    // ─────────────────────────────────────────────────────────────────────────
    public function cancel(Request $request)
    {
        return view('payment.cancel', [
            'orderId' => null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEB: Stripe Webhook Handler
    // POST /stripe/webhook   (no auth, no CSRF)
    // ─────────────────────────────────────────────────────────────────────────
    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');
        $secret    = config('services.stripe.webhook_secret');

        // ── Verify Stripe signature ─────────────────────────────────────────
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature.', Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            Log::warning('Stripe webhook: malformed payload', ['error' => $e->getMessage()]);
            return response('Malformed payload.', Response::HTTP_BAD_REQUEST);
        }

        // ── Route events ────────────────────────────────────────────────────
        match ($event->type) {
            'payment_intent.succeeded'     => $this->handlePaymentIntentSucceeded($event->data->object),
            'checkout.session.completed'   => $this->handleCheckoutCompleted($event->data->object),
            'checkout.session.expired'     => $this->handleCheckoutExpired($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            'charge.refunded'              => $this->handleChargeRefunded($event->data->object),
            default                        => null, // Ignore unhandled events
        };

        // Always return 200 so Stripe stops retrying
        return response('Webhook received.', Response::HTTP_OK);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle payment_intent.succeeded
    // ─────────────────────────────────────────────────────────────────────────
    private function handlePaymentIntentSucceeded(object $intent): void
    {
        $paymentIntentId = $intent->id;

        // ── Concurrency Prevention: Atomic lock on the specific PaymentIntent ──
        $lock = Cache::lock('stripe_pi_' . $paymentIntentId, 15);
        try {
            $lock->block(5);
        } catch (\Throwable $e) {}

        try {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

            // If payment record already exists (created by frontend via /api/orders or checkout.session.completed)
            if ($payment) {
                if ($payment->status !== 'completed') {
                    DB::transaction(function () use ($payment) {
                        $payment->update(['status' => 'completed']);
                        $payment->order()->update(['status' => 'processing']);
                    });
                }
                Log::info('Stripe webhook: payment_intent.succeeded already recorded', ['intent_id' => $paymentIntentId]);
                return;
            }

            $userId        = isset($intent->metadata->user_id) ? (int) $intent->metadata->user_id : null;
            $addressId     = isset($intent->metadata->address_id) ? (int) $intent->metadata->address_id : null;
            $customerEmail = $intent->metadata->customer_email ?? null;
            $couponCode    = $intent->metadata->coupon_code ?? null;

            if (!$userId || !$addressId) {
                Log::warning('Stripe webhook: payment_intent.succeeded missing user or address metadata', ['intent_id' => $paymentIntentId]);
                return;
            }

            $this->fulfillOrderFromStripe(
                $userId,
                $addressId,
                $customerEmail,
                $couponCode,
                $paymentIntentId,
                (int) $intent->amount
            );

            Log::info('Stripe webhook: order successfully fulfilled from payment_intent.succeeded', ['intent_id' => $paymentIntentId]);
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {}
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle checkout.session.completed
    // ─────────────────────────────────────────────────────────────────────────
    private function handleCheckoutCompleted(object $session): void
    {
        $stripeSessionId = $session->id;
        $paymentIntentId = is_object($session->payment_intent)
            ? $session->payment_intent->id
            : ($session->payment_intent ?? null);

        if (!$paymentIntentId) {
            Log::warning('Stripe webhook: checkout.session.completed missing payment_intent', ['session_id' => $stripeSessionId]);
            return;
        }

        $lock = Cache::lock('stripe_pi_' . $paymentIntentId, 15);
        try {
            $lock->block(5);
        } catch (\Throwable $e) {}

        try {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->orWhere('stripe_session_id', $stripeSessionId)
                ->first();

            // If payment record already exists
            if ($payment) {
                if ($payment->status !== 'completed' || empty($payment->stripe_session_id)) {
                    $payment->update([
                        'status'            => 'completed',
                        'stripe_session_id' => $stripeSessionId,
                    ]);
                    $payment->order()->update(['status' => 'processing']);
                }
                Log::info('Stripe webhook: checkout.session.completed already processed', ['session_id' => $stripeSessionId]);
                return;
            }

            $userId        = isset($session->metadata->user_id) ? (int) $session->metadata->user_id : null;
            $addressId     = isset($session->metadata->address_id) ? (int) $session->metadata->address_id : null;
            $customerEmail = $session->metadata->customer_email ?? null;
            $couponCode    = $session->metadata->coupon_code ?? null;
            $amountTotal   = isset($session->amount_total) ? (int) $session->amount_total : 0;

            if (!$userId || !$addressId) {
                Log::warning('Stripe webhook: checkout.session.completed missing user or address metadata', ['session_id' => $stripeSessionId]);
                return;
            }

            $order = $this->fulfillOrderFromStripe(
                $userId,
                $addressId,
                $customerEmail,
                $couponCode,
                $paymentIntentId,
                $amountTotal,
                $stripeSessionId
            );

            if ($order) {
                Log::info('Stripe webhook: order successfully fulfilled from checkout.session.completed', [
                    'order_id'   => $order->id,
                    'session_id' => $stripeSessionId,
                ]);
            }
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {}
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle checkout.session.expired (abandoned session)
    // ─────────────────────────────────────────────────────────────────────────
    private function handleCheckoutExpired(object $session): void
    {
        Log::info('Stripe webhook: checkout.session.expired (abandoned session)', ['session_id' => $session->id]);

        // If a legacy pending payment existed from older sessions, clean it up safely
        $payment = Payment::where('stripe_session_id', $session->id)->first();
        if ($payment && $payment->status === 'pending') {
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => 'failed']);
                $order = $payment->order;
                if ($order) {
                    $order->update(['status' => 'cancelled']);
                    CouponUsage::where('order_id', $order->id)->delete();
                }
            });
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Unified Stripe Order Fulfillment
    // ─────────────────────────────────────────────────────────────────────────
    private function fulfillOrderFromStripe(
        int $userId,
        int $addressId,
        ?string $customerEmail,
        ?string $couponCode,
        string $paymentIntentId,
        int $chargedCents,
        ?string $sessionId = null
    ): ?Order {
        $address = Address::where('id', $addressId)->where('user_id', $userId)->first();
        $cart    = Cart::where('user_id', $userId)->with(['items.product', 'items.variant.optionValues.option', 'user'])->first();

        if (!$address || !$cart || $cart->items->isEmpty()) {
            $this->refundPaymentIntent($paymentIntentId, 'Cart empty or address missing on webhook fulfillment');
            return null;
        }

        // Check stock availability
        foreach ($cart->items as $cartItem) {
            if (!$cartItem->product) {
                $this->refundPaymentIntent($paymentIntentId, 'Product no longer available on webhook fulfillment');
                return null;
            }
            $availableStock = $cartItem->variant ? (int) $cartItem->variant->stock : (int) $cartItem->product->stock;
            if ($availableStock < $cartItem->quantity) {
                $this->refundPaymentIntent($paymentIntentId, 'Stock depleted before webhook fulfillment');
                return null;
            }
        }

        // Calculate totals
        $subtotal       = $cart->items->sum(fn($i) => ($i->variant ? $i->variant->effective_price : (float) $i->product->price) * $i->quantity);
        $coupon         = null;
        $discountAmount = 0.0;

        if (!empty($couponCode)) {
            $coupon = Coupon::where('code', trim($couponCode))->first();
            if ($coupon && !$coupon->globalValidationError()) {
                $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $userId)->exists();
                if (!$alreadyUsed && (!$coupon->min_order_amount || $subtotal >= (float) $coupon->min_order_amount)) {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                } else {
                    $coupon = null;
                }
            } else {
                $coupon = null;
            }
        }

        $totalAmount   = round($subtotal - $discountAmount, 2);
        $expectedCents = (int) round($totalAmount * 100);

        if ($chargedCents > 0 && abs($chargedCents - $expectedCents) > 1) {
            $this->refundPaymentIntent($paymentIntentId, 'Amount mismatch on webhook fulfillment');
            return null;
        }

        // Fulfill order in database transaction
        try {
            return DB::transaction(function () use ($userId, $customerEmail, $address, $cart, $totalAmount, $coupon, $paymentIntentId, $sessionId) {
                $expectedDeliveryDate = now()->addDays(4)->toDateString();
                $order = Order::create([
                    'user_id'                => $userId,
                    'customer_email'         => $customerEmail ?: ($cart->user?->email ?? ''),
                    'address_id'             => $address->id,
                    'shipping_name'          => $address->name,
                    'shipping_phone'         => $address->phone,
                    'shipping_address_line1' => $address->address_line1,
                    'shipping_address_line2' => $address->address_line2,
                    'shipping_city'          => $address->city,
                    'shipping_state'         => $address->state,
                    'shipping_postal_code'   => $address->postal_code,
                    'shipping_country'       => $address->country,
                    'status'                 => 'processing',
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
                        if ($variant) {
                            $itemPrice = $variant->effective_price;
                            $variantName = $variant->title;
                            $variantBeforeStock = (int) $variant->stock;
                            $variantAfterStock  = max(0, $variantBeforeStock - $cartItem->quantity);
                            $variant->update(['stock' => $variantAfterStock]);
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
                        'user_id'            => $userId,
                        'type'               => 'sale',
                        'quantity'           => -$cartItem->quantity,
                        'quantity_before'    => $variant ? $variantBeforeStock : $beforeStock,
                        'quantity_after'     => $variant ? $variantAfterStock : $afterStock,
                        'reference_id'       => (string) $order->id,
                        'notes'              => "Order #{$order->id} placed via Stripe" . ($variantName ? " ({$variantName})" : ''),
                    ]);
                }

                if ($coupon) {
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id'   => $userId,
                        'order_id'  => $order->id,
                    ]);
                }

                Payment::create([
                    'order_id'                 => $order->id,
                    'payment_method'           => 'card',
                    'amount'                   => $totalAmount,
                    'status'                   => 'completed',
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_session_id'        => $sessionId,
                    'transaction_reference'    => $paymentIntentId,
                ]);

                $cart->items()->delete();

                return $order;
            });
        } catch (\Exception $e) {
            Log::error('Stripe order fulfillment failed, triggering refund', ['error' => $e->getMessage(), 'intent_id' => $paymentIntentId]);
            $this->refundPaymentIntent($paymentIntentId, 'Order fulfillment database transaction failed');
            return null;
        }
    }

    private function refundPaymentIntent(string $paymentIntentId, string $reason): void
    {
        try {
            $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
            ]);
            Log::info("Stripe refund issued for intent {$paymentIntentId}: {$reason}");
        } catch (\Exception $e) {
            Log::error("Stripe refund failed for intent {$paymentIntentId}: " . $e->getMessage());
        }
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle payment_intent.payment_failed
    // ─────────────────────────────────────────────────────────────────────────
    private function handlePaymentFailed(object $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment && $payment->status === 'pending') {
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => 'failed']);
                $order = $payment->order;
                $order->update(['status' => 'cancelled']);

                // Restore coupon usage if order is cancelled
                CouponUsage::where('order_id', $order->id)->delete();

                // Restore stock and record inventory logs
                foreach ($order->items as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $before = (int) $product->stock;
                        $after  = $before + $item->quantity;
                        $product->update(['stock' => $after]);

                        InventoryLog::create([
                            'product_id'      => $product->id,
                            'user_id'         => $order->user_id,
                            'type'            => 'return',
                            'quantity'        => $item->quantity,
                            'quantity_before' => $before,
                            'quantity_after'  => $after,
                            'reference_id'    => (string) $order->id,
                            'notes'           => "Stock restored: Stripe payment failed for order #{$order->id}",
                        ]);
                    }
                }
            });

            Log::info('Stripe webhook: payment failed, order cancelled and stock restored', ['order_id' => $payment->order_id]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle charge.refunded
    // ─────────────────────────────────────────────────────────────────────────
    private function handleChargeRefunded(object $charge): void
    {
        $paymentIntentId = $charge->payment_intent ?? null;
        $payment = $paymentIntentId ? Payment::where('stripe_payment_intent_id', $paymentIntentId)->first() : null;

        if ($payment && $payment->status !== 'refunded') {
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => 'refunded']);
                $order = $payment->order;
                if ($order && $order->status !== 'cancelled') {
                    $order->update(['status' => 'cancelled']);

                    // Restore coupon usage
                    CouponUsage::where('order_id', $order->id)->delete();

                    // Restore stock and record inventory logs
                    foreach ($order->items as $item) {
                        $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                        if ($product) {
                            $before = (int) $product->stock;
                            $after  = $before + $item->quantity;
                            $product->update(['stock' => $after]);

                            InventoryLog::create([
                                'product_id'      => $product->id,
                                'user_id'         => $order->user_id,
                                'type'            => 'return',
                                'quantity'        => $item->quantity,
                                'quantity_before' => $before,
                                'quantity_after'  => $after,
                                'reference_id'    => (string) $order->id,
                                'notes'           => "Stock restored: Stripe charge refunded for order #{$order->id}",
                            ]);
                        }
                    }
                }
            });

            Log::info('Stripe webhook: charge refunded, order cancelled and stock restored', ['order_id' => $payment->order_id]);
        }
    }
}
