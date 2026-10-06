<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\OrderCancellationService;
use App\Services\PaymentFulfillmentService;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeController extends Controller
{
    private StripeClient $stripe;
    private PaymentFulfillmentService $fulfillmentService;
    private OrderCancellationService $cancellationService;

    public function __construct(
        ?StripeClient $stripe = null,
        ?PaymentFulfillmentService $fulfillmentService = null,
        ?OrderCancellationService $cancellationService = null
    ) {
        $this->stripe = $stripe ?? (app()->bound(StripeClient::class)
            ? app(StripeClient::class)
            : new StripeClient(config('services.stripe.secret')));
        $this->fulfillmentService  = $fulfillmentService  ?? app(PaymentFulfillmentService::class);
        $this->cancellationService = $cancellationService ?? app(OrderCancellationService::class);
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
                $unitPrice = $item->variant ? $item->variant->effective_price : (float) $item->product->price;
                $discountedPrice = $unitPrice * $discountRatio;
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
    // Lock + idempotency handled inside PaymentFulfillmentService::fulfill()
    // ─────────────────────────────────────────────────────────────────────────
    private function handlePaymentIntentSucceeded(object $intent): void
    {
        $paymentIntentId = $intent->id;

        $userId        = isset($intent->metadata->user_id) ? (int) $intent->metadata->user_id : null;
        $addressId     = isset($intent->metadata->address_id) ? (int) $intent->metadata->address_id : null;
        $customerEmail = $intent->metadata->customer_email ?? null;
        $couponCode    = $intent->metadata->coupon_code ?? null;

        if (!$userId || !$addressId) {
            Log::warning('Stripe webhook: payment_intent.succeeded missing user or address metadata', ['intent_id' => $paymentIntentId]);
            return;
        }

        $order = $this->fulfillOrderFromStripe(
            $userId,
            $addressId,
            $customerEmail,
            $couponCode,
            $paymentIntentId,
            // amount may be absent when the intent is already captured via checkout.session
            (int) ($intent->amount ?? 0)
        );

        if ($order) {
            Log::info('Stripe webhook: order fulfilled from payment_intent.succeeded', [
                'order_id'  => $order->id,
                'intent_id' => $paymentIntentId,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle checkout.session.completed
    // Lock + idempotency handled inside PaymentFulfillmentService::fulfill()
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
            Log::info('Stripe webhook: order fulfilled from checkout.session.completed', [
                'order_id'   => $order->id,
                'session_id' => $stripeSessionId,
            ]);
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
        try {
            return $this->fulfillmentService->fulfill(
                paymentIntentId: $paymentIntentId,
                sessionId: $sessionId,
                userId: $userId,
                addressId: $addressId,
                customerEmail: $customerEmail,
                couponCode: $couponCode,
                chargedCents: $chargedCents
            );
        } catch (\Throwable $e) {
            Log::error('Stripe order fulfillment failed (no auto-refund on DB exception)', [
                'error'     => $e->getMessage(),
                'intent_id' => $paymentIntentId,
            ]);
            return null;
        }
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle payment_intent.payment_failed
    // Stripe declined the charge — cancel order WITHOUT issuing a refund.
    // ─────────────────────────────────────────────────────────────────────────
    private function handlePaymentFailed(object $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment || !in_array($payment->status, ['pending', 'completed'], true)) {
            return;
        }

        $payment->update(['status' => 'failed']);

        $order = $payment->order;
        if ($order) {
            // No Stripe refund: charge was never captured (payment failed on Stripe side)
            $this->cancellationService->cancel(
                order:       $order,
                actorUserId: $order->user_id,
                actor:       'stripe_payment_failed',
                issueRefund: false
            );
            Log::info('Stripe webhook: payment_intent.payment_failed — order cancelled, no refund issued', [
                'order_id'  => $order->id,
                'intent_id' => $paymentIntent->id,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle charge.refunded
    // Stripe confirmed the refund — cancel order WITHOUT re-issuing a Stripe refund
    // (refund already happened on Stripe's side).
    // ─────────────────────────────────────────────────────────────────────────
    private function handleChargeRefunded(object $charge): void
    {
        $paymentIntentId = $charge->payment_intent ?? null;
        $payment = $paymentIntentId
            ? Payment::where('stripe_payment_intent_id', $paymentIntentId)->first()
            : null;

        if (!$payment) {
            return;
        }

        // Mark payment refunded (service won't issue another Stripe refund
        // because issueRefund=false — Stripe already did it)
        Payment::where('id', $payment->id)
            ->whereNotIn('status', ['refunded'])
            ->update(['status' => 'refunded']);

        $order = $payment->order;
        if ($order) {
            $this->cancellationService->cancel(
                order:       $order,
                actorUserId: $order->user_id,
                actor:       'stripe_charge_refunded',
                issueRefund: false   // Stripe already issued the refund
            );
            Log::info('Stripe webhook: charge.refunded — order cancelled, stock restored', [
                'order_id'   => $order->id,
                'payment_id' => $payment->id,
            ]);
        }
    }
}
