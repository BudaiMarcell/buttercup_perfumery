<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $methods = PaymentMethod::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return PaymentMethodResource::collection($methods);
    }

    public function store(Request $request)
    {
        $now = now();

        $validated = $request->validate([
            'brand'      => 'required|string|max:32',
            'last_four'  => ['required', 'string', 'regex:/^\d{4}$/'],

            'exp_month'  => 'required|integer|min:1|max:12',
            'exp_year'   => 'required|integer|min:' . $now->year . '|max:' . ($now->year + 30),
            'is_default' => 'boolean',
        ]);

        $validated['brand'] = ucfirst(strtolower($validated['brand']));

        return DB::transaction(function () use ($request, $validated) {

            if (!empty($validated['is_default'])) {
                PaymentMethod::where('user_id', $request->user()->id)
                    ->update(['is_default' => false]);
            }

            $method = PaymentMethod::updateOrCreate(
                [
                    'user_id'   => $request->user()->id,
                    'last_four' => $validated['last_four'],
                    'exp_month' => $validated['exp_month'],
                    'exp_year'  => $validated['exp_year'],
                ],
                [
                    'brand'      => $validated['brand'],
                    'is_default' => $validated['is_default'] ?? false,
                ]
            );

            return new PaymentMethodResource($method);
        });
    }

    public function update(Request $request, int $id)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'is_default' => 'sometimes|boolean',
        ]);

        return DB::transaction(function () use ($request, $method, $validated) {
            if (!empty($validated['is_default']) && $validated['is_default']) {
                PaymentMethod::where('user_id', $request->user()->id)
                    ->where('id', '!=', $method->id)
                    ->update(['is_default' => false]);
            }

            $method->update($validated);

            return new PaymentMethodResource($method->fresh());
        });
    }

    public function destroy(Request $request, int $id)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $method->delete();

        return response()->json(['message' => 'Payment method removed.']);
    }
}
