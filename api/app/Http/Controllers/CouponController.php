<?php

namespace App\Http\Controllers;

use App\Models\Coupons;
use Illuminate\Http\Request;

/**
 * Public coupon validation endpoint. Called by the storefront checkout
 * page when the user types a code and hits Apply.
 *
 * Returns a normalized response with the discount type + value so the
 * frontend can compute the discounted total. The same logic runs again
 * server-side at order placement time (OrderController::store), so even
 * if someone bypasses this endpoint they can't fake a discount.
 *
 * Response shape:
 *   { valid: true,  code, discount_type, discount_value, message }
 *   { valid: false, message }
 */
class CouponController extends Controller
{
    public function validateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:32',
        ]);

        $code = mb_strtoupper(trim($validated['code']));

        $coupon = Coupons::where('coupon_code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'valid'   => false,
                'message' => 'Ismeretlen kuponkód.',
            ]);
        }

        if (!$coupon->is_active) {
            return response()->json([
                'valid'   => false,
                'message' => 'Ez a kupon nem aktív.',
            ]);
        }

        // Use whereDate-equivalent logic: compare just the date part,
        // not the time. A coupon valid "until 2026-12-31" should still
        // work at 23:59 on that day.
        if ($coupon->expiry_date && $coupon->expiry_date < now()->toDateString()) {
            return response()->json([
                'valid'   => false,
                'message' => 'Ez a kupon már lejárt.',
            ]);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'valid'   => false,
                'message' => 'Ezt a kupont már túl sokszor használták.',
            ]);
        }

        return response()->json([
            'valid'          => true,
            'code'           => $coupon->coupon_code,
            'discount_type'  => $coupon->discount_type,   // 'percentage' | 'fixed'
            'discount_value' => (float) $coupon->discount_value,
            'message'        => $this->successMessage($coupon),
        ]);
    }

    private function successMessage(Coupons $coupon): string
    {
        if ($coupon->discount_type === 'percentage') {
            $pct = rtrim(rtrim(number_format($coupon->discount_value, 2, '.', ''), '0'), '.');
            return "{$pct}% kedvezmény alkalmazva.";
        }
        $ft = number_format($coupon->discount_value, 0, ',', ' ');
        return "{$ft} Ft kedvezmény alkalmazva.";
    }
}
