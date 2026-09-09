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
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeController extends Controller
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
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
            'address_id'   => ['required', 'integer', 'exists:addresses,id'],
            'coupon_code'  => ['nullable', 'string'],
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
            ->with(['items.product'])
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

            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for '{$item->product->name}'. Available: {$item->product->stock}, Requested: {$item->quantity}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // ── 4. Calculate totals ────────────────────────────────────────────
        $subtotal = $cart->items->sum(fn ($item) => (float) $item->product->price * $item->quantity);

        $coupon         = null;
        $discountAmount = 0.0;

        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', trim($validated['coupon_code']))
                ->where('is_active', true)
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coupon requires a minimum order amount of $' . number_format($coupon->min_order_amount, 2),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $discountAmount = ($subtotal * (float) $coupon->discount_percent) / 100;
            if ($coupon->max_discount && $discountAmount > (float) $coupon->max_discount) {
                $discountAmount = (float) $coupon->max_discount;
            }
        }

        $totalAmount = max(0, round($subtotal - $discountAmount, 2));

        // ── 5. Create Order, Payment & clear cart (DB transaction) ─────────
        $order = DB::transaction(function () use ($user, $address, $cart, $totalAmount, $validated, $coupon) {

            $order = Order::create([
                'user_id'      => $user->id,
                'address_id'   => $address->id,
                'status'       => 'pending',
                'total_amount' => $totalAmount,
            ]);

            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity'   => $cartItem->quantity,
                    'price'      => (float) $cartItem->product->price,
                ]);

                Product::where('id', $cartItem->product_id)
                    ->decrement('stock', $cartItem->quantity);
            }

            if ($coupon) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id'   => $user->id,
                    'order_id'  => $order->id,
                ]);
            }

            // Payment stays 'pending' — the webhook will mark it 'completed'
            Payment::create([
                'order_id'       => $order->id,
                'payment_method' => 'card',
                'amount'         => $totalAmount,
                'status'         => 'pending',
                // stripe_session_id filled after Stripe session is created below
            ]);

            $cart->items()->delete();

            return $order;
        });

        // ── 6. Build Stripe line items ──────────────────────────────────────
        $lineItems = $cart->items->map(fn ($item) => [
            'price_data' => [
                'currency'     => 'usd',
                'unit_amount'  => (int) round((float) $item->product->price * 100), // cents
                'product_data' => [
                    'name' => $item->product->name,
                ],
            ],
            'quantity' => $item->quantity,
        ])->values()->toArray();

        // If a coupon discount applies, add it as a negative line item
        if ($discountAmount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => -(int) round($discountAmount * 100),
                    'product_data' => [
                        'name' => 'Coupon Discount (' . ($coupon->code ?? '') . ')',
                    ],
                ],
                'quantity' => 1,
            ];
        }

        // ── 7. Create Stripe Checkout Session ──────────────────────────────
        try {
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items'           => $lineItems,
                'mode'                 => 'payment',
                'success_url'          => url('/payment/success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => url('/payment/cancel') . '?order_id=' . $order->id,
                'client_reference_id'  => (string) $order->id,
                'metadata'             => [
                    'order_id' => $order->id,
                    'user_id'  => $user->id,
                ],
                'customer_email' => $user->email,
            ]);
        } catch (\Exception $e) {
            // Roll back order creation on Stripe error
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
                $order->payments()->delete();
                $order->items()->delete();
                $order->delete();
            });

            Log::error('Stripe session creation failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initiate payment. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // ── 8. Store stripe_session_id on the Payment record ───────────────
        Payment::where('order_id', $order->id)
            ->where('payment_method', 'card')
            ->update(['stripe_session_id' => $session->id]);

        return response()->json([
            'success'     => true,
            'message'     => 'Stripe Checkout Session created.',
            'session_url' => $session->url,
            'session_id'  => $session->id,
            'order_id'    => $order->id,
        ], Response::HTTP_CREATED);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEB: Stripe Success Landing Page
    // GET /payment/success?session_id=cs_xxx
    // ─────────────────────────────────────────────────────────────────────────
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id', '');

        // Retrieve session from Stripe to get the order ID
        $orderId = null;

        if ($sessionId) {
            try {
                $session = $this->stripe->checkout->sessions->retrieve($sessionId, [
                    'expand' => ['payment_intent'],
                ]);
                $orderId = $session->client_reference_id ?? $session->metadata->order_id ?? null;
            } catch (\Exception $e) {
                Log::warning('Stripe success page: could not retrieve session', ['session_id' => $sessionId]);
            }
        }

        return view('payment.success', [
            'sessionId' => $sessionId,
            'orderId'   => $orderId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEB: Stripe Cancel Landing Page
    // GET /payment/cancel?order_id=xxx
    // ─────────────────────────────────────────────────────────────────────────
    public function cancel(Request $request)
    {
        $orderId = $request->get('order_id');

        return view('payment.cancel', [
            'orderId' => $orderId,
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
            'checkout.session.completed'   => $this->handleCheckoutCompleted($event->data->object),
            'checkout.session.expired'     => $this->handleCheckoutExpired($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default                        => null, // Ignore unhandled events
        };

        // Always return 200 so Stripe stops retrying
        return response('Webhook received.', Response::HTTP_OK);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle checkout.session.completed
    // ─────────────────────────────────────────────────────────────────────────
    private function handleCheckoutCompleted(object $session): void
    {
        $stripeSessionId  = $session->id;
        $paymentIntentId  = $session->payment_intent ?? null;

        $payment = Payment::where('stripe_session_id', $stripeSessionId)->first();

        if (!$payment) {
            Log::warning('Stripe webhook: payment record not found for session', ['session_id' => $stripeSessionId]);
            return;
        }

        // Idempotency guard – skip if already processed
        if ($payment->status === 'completed') {
            Log::info('Stripe webhook: duplicate event ignored', ['session_id' => $stripeSessionId]);
            return;
        }

        DB::transaction(function () use ($payment, $paymentIntentId) {
            $payment->update([
                'status'                    => 'completed',
                'stripe_payment_intent_id'  => $paymentIntentId,
            ]);

            $payment->order()->update(['status' => 'processing']);
        });

        Log::info('Stripe webhook: order confirmed', [
            'order_id'         => $payment->order_id,
            'payment_intent'   => $paymentIntentId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle checkout.session.expired (user didn't complete payment)
    // ─────────────────────────────────────────────────────────────────────────
    private function handleCheckoutExpired(object $session): void
    {
        $payment = Payment::where('stripe_session_id', $session->id)->first();

        if ($payment && $payment->status === 'pending') {
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => 'failed']);
                $payment->order()->update(['status' => 'cancelled']);

                // Restore stock
                foreach ($payment->order->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
            });

            Log::info('Stripe webhook: session expired, order cancelled', ['order_id' => $payment->order_id]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Handle payment_intent.payment_failed
    // ─────────────────────────────────────────────────────────────────────────
    private function handlePaymentFailed(object $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment && $payment->status === 'pending') {
            $payment->update(['status' => 'failed']);
            $payment->order()->update(['status' => 'cancelled']);

            Log::info('Stripe webhook: payment failed', ['order_id' => $payment->order_id]);
        }
    }
}
