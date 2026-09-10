<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CouponController extends Controller
{
    /**
     * List all currently active coupons.
     */
    public function index(): JsonResponse
    {
        $coupons = Coupon::where('is_active', true)
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $coupons,
        ], Response::HTTP_OK);
    }

    /**
     * Validate a coupon against a requested order subtotal.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $subtotal = (float) $validated['order_amount'];

        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code is invalid or has expired.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if user already used this coupon (if authenticated)
        $user = $request->user();
        if ($user) {
            $alreadyUsed = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already redeemed coupon '{$coupon->code}'. Each coupon can only be used once per customer.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => "This coupon requires a minimum subtotal of $" . number_format($coupon->min_order_amount, 2),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $discount = ($subtotal * (float) $coupon->discount_percent) / 100;

        if ($coupon->max_discount && $discount > (float) $coupon->max_discount) {
            $discount = (float) $coupon->max_discount;
        }

        $discount = round($discount, 2);
        $finalTotal = max(0, round($subtotal - $discount, 2));

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'data' => [
                'code' => $coupon->code,
                'discount_percent' => (float) $coupon->discount_percent,
                'discount_amount' => $discount,
                'original_amount' => $subtotal,
                'final_amount' => $finalTotal,
            ],
        ], Response::HTTP_OK);
    }
}
