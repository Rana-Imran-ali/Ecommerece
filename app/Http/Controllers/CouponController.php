<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class CouponController extends Controller
{
    /**
     * List all currently active, non-expired, non-exhausted coupons.
     */
    public function index(): JsonResponse
    {
        $coupons = Coupon::where('is_active', true)
            ->latest('id')
            ->get()
            ->filter(fn($c) => $c->isCurrentlyValid())
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $coupons,
        ], Response::HTTP_OK);
    }

    /**
     * Validate a coupon against a requested order subtotal.
     * Enforces: active, not expired, not exhausted, min order, per-user single-use.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'         => ['required', 'string'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $code     = strtoupper(trim($validated['code']));
        $subtotal = (float) $validated['order_amount'];

        $coupon = Coupon::where('code', $code)->first();

        // ── 1. Existence + active + expiry + max_uses ────────────────────────
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code is invalid or has expired.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($error = $coupon->globalValidationError()) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── 2. Per-user single-use check (optional, needs auth) ───────────────
        $user = $request->user();
        if (!$user && ($token = $request->bearerToken())) {
            try {
                if (!Cache::has('token_blacklist_' . sha1($token))) {
                    $decrypted = Crypt::decryptString($token);
                    $parts     = explode('|', $decrypted);
                    if (count($parts) >= 2 && (time() - (int) $parts[1] <= 30 * 86400)) {
                        $user = \App\Models\User::find((int) $parts[0]);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore invalid token on public endpoint
            }
        }

        if ($user) {
            $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already redeemed coupon '{$coupon->code}'. Each coupon can only be used once per customer.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // ── 3. Minimum order amount ───────────────────────────────────────────
        if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon requires a minimum subtotal of $' . number_format($coupon->min_order_amount, 2),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── 4. Calculate discount (capped at subtotal, supports fixed + percent) ──
        $discount   = $coupon->calculateDiscount($subtotal);
        $finalTotal = round($subtotal - $discount, 2);

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'data'    => [
                'code'             => $coupon->code,
                'discount_type'    => $coupon->discount_type,
                'discount_percent' => (float) $coupon->discount_percent,
                'discount_amount'  => $discount,
                'original_amount'  => $subtotal,
                'final_amount'     => $finalTotal,
            ],
        ], Response::HTTP_OK);
    }
}
