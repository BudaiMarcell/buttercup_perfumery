<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewCouponAvailable;
use App\Models\Coupons;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminCouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupons::query();

        if ($request->filled('search')) {
            $query->where('coupon_code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('status')) {
            $today = now()->toDateString();
            if ($request->status === 'expired') {
                $query->where('expiry_date', '<', $today);
            } elseif ($request->status === 'active') {
                $query->where('expiry_date', '>=', $today)->where('is_active', true);
            }
        }

        $coupons = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($coupons);
    }

    public function show($id)
    {
        $coupon = Coupons::findOrFail($id);
        return response()->json($coupon);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coupon_code'    => 'nullable|string|max:32|unique:coupons,coupon_code',
            'discount_type'  => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'expiry_date'    => 'required|date',
            'usage_limit'    => 'nullable|integer|min:1',
            'is_active'      => 'boolean',
            // Opt-in: when true, every verified user receives a one-shot
            // mail announcing the new code. Defaults to false so a quick
            // edit doesn't spam the customer base by accident.
            'notify_users'   => 'sometimes|boolean',
        ]);

        $notifyUsers = (bool) ($data['notify_users'] ?? false);
        unset($data['notify_users']);

        if (empty($data['coupon_code'])) {
            do {
                $code = strtoupper(Str::random(10));
            } while (Coupons::where('coupon_code', $code)->exists());
            $data['coupon_code'] = $code;
        }

        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            return response()->json([
                'message' => 'A százalékos kedvezmény nem lehet nagyobb 100-nál.',
            ], 422);
        }

        $data['used_count'] = 0;
        $data['is_active']  = $data['is_active'] ?? true;

        $coupon = Coupons::create($data);

        AuditLogger::log('created', 'Coupon', $coupon->id,
            "Új kupon létrehozva: {$coupon->coupon_code}",
            ['new' => $coupon->toArray()]);

        // Broadcast to all verified users — only if the admin asked for it
        // AND the coupon is actually usable (active + not in the past).
        // Each chunk is queued individually; the queue worker fans out
        // without blocking the API response.
        if ($notifyUsers && $coupon->is_active && $coupon->expiry_date >= now()->toDateString()) {
            $this->broadcastCouponEmail($coupon);
        }

        return response()->json($coupon, 201);
    }

    /**
     * Queue a NewCouponAvailable mail for every verified user, in chunks
     * of 200 to keep memory bounded. Failures during enqueue are logged
     * but never bubbled up — the coupon itself is already saved and the
     * admin shouldn't see a 500 because mail is misconfigured.
     */
    private function broadcastCouponEmail(Coupons $coupon): void
    {
        try {
            User::whereNotNull('email_verified_at')
                ->select(['id', 'name', 'email'])
                ->chunkById(200, function ($users) use ($coupon) {
                    foreach ($users as $user) {
                        try {
                            Mail::to($user->email)
                                ->queue(new NewCouponAvailable($coupon, $user));
                        } catch (\Throwable $inner) {
                            // One bad address shouldn't kill the whole
                            // batch — keep going for the others.
                            Log::warning('Failed to queue coupon email for user', [
                                'user_id'   => $user->id,
                                'coupon_id' => $coupon->id,
                                'error'     => $inner->getMessage(),
                            ]);
                        }
                    }
                });
        } catch (\Throwable $e) {
            Log::error('Coupon broadcast aborted', [
                'coupon_id' => $coupon->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupons::findOrFail($id);

        $data = $request->validate([
            'coupon_code'    => 'sometimes|string|max:32|unique:coupons,coupon_code,' . $id,
            'discount_type'  => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'expiry_date'    => 'sometimes|date',
            'usage_limit'    => 'nullable|integer|min:1',
            'is_active'      => 'sometimes|boolean',
        ]);

        $type  = $data['discount_type']  ?? $coupon->discount_type;
        $value = $data['discount_value'] ?? $coupon->discount_value;
        if ($type === 'percentage' && $value > 100) {
            return response()->json([
                'message' => 'A százalékos kedvezmény nem lehet nagyobb 100-nál.',
            ], 422);
        }

        $old = $coupon->only(array_keys($data));
        $coupon->update($data);

        AuditLogger::log('updated', 'Coupon', $coupon->id,
            "Kupon frissítve: {$coupon->coupon_code}",
            ['old' => $old, 'new' => $data]);

        return response()->json($coupon);
    }

    public function destroy($id)
    {
        $coupon = Coupons::findOrFail($id);
        $code = $coupon->coupon_code;
        $coupon->delete();

        AuditLogger::log('deleted', 'Coupon', $id, "Kupon törölve: {$code}");

        return response()->json(['message' => 'Kupon törölve.']);
    }
}
