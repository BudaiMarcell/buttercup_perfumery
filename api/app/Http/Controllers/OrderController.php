<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Mail\OrderStatusChanged;
use App\Mail\StockLowAlert;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\OrderResource;

/**
 * Stock below this number triggers an admin alert mail when an order
 * decrement crosses the threshold. Hard-coded for now; lift to a
 * settings table when there's a second knob worth configuring.
 */
const STOCK_LOW_THRESHOLD = 5;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product.primaryImage', 'address'])
            ->orderBy('created_at', 'desc')
            ->get();

        return OrderResource::collection($orders);
    }

    public function show(Request $request, int $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['items.product.primaryImage', 'address'])
            ->firstOrFail();

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_id'     => 'required|exists:addresses,id',
            'payment_method' => 'required|string|max:50',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Capture products that crossed the low-stock threshold during
        // this transaction so we can fire admin alerts AFTER the commit
        // (mail dispatch inside a transaction risks sending for a row
        // that gets rolled back on a later failure).
        $crossedLowStock = [];

        $order = DB::transaction(function () use ($validated, $request, &$crossedLowStock) {

            $total = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    abort(422, "Nincs elegendő készlet: {$product->name}");
                }

                $subtotal      = $product->price * $item['quantity'];
                $total        += $subtotal;
                $orderItems[]  = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $subtotal,
                ];

                $wasAbove = $product->stock_quantity >= STOCK_LOW_THRESHOLD;
                $product->decrement('stock_quantity', $item['quantity']);

                // Refresh from DB so the post-decrement value is correct
                // (decrement is an atomic SQL op, not a model setter).
                $product->refresh();

                if ($wasAbove && $product->stock_quantity < STOCK_LOW_THRESHOLD) {
                    $crossedLowStock[] = $product->id;
                }
            }

            $order = Order::create([
                'user_id'        => $request->user()->id,
                'address_id'     => $validated['address_id'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'status'         => 'pending',
                'total_amount'   => $total,
                'notes'          => $validated['notes'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    ...$item,
                ]);
            }

            return $order;
        });

        try {
            Mail::to($order->user->email)->queue(new OrderPlaced($order));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue order confirmation email', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        // Stock-low admin alerts. Sent only for products that crossed
        // the threshold during THIS order — re-orders of the same
        // already-low product won't re-spam admins.
        if (!empty($crossedLowStock)) {
            try {
                $adminEmails = Admin::pluck('email')->filter()->all();
                if (count($adminEmails) > 0) {
                    foreach ($crossedLowStock as $productId) {
                        $product = Product::find($productId);
                        if (!$product) continue;
                        Mail::to($adminEmails)
                            ->queue(new StockLowAlert($product, STOCK_LOW_THRESHOLD));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to queue stock-low alert', [
                    'product_ids' => $crossedLowStock,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return new OrderResource($order->load(['items.product', 'address']));
    }

    /**
     * Customer cancels their own order. Only allowed while the order is
     * still in the `pending` state — once the admin moves it to
     * `processing`, the warehouse has started preparing it and a
     * customer-side cancel would race with whatever staff is doing.
     *
     * Side-effects, all wrapped in a single transaction so we never
     * end up with "status=canceled" but the stock never restored:
     *   - Status flipped to 'canceled'
     *   - Each line item's quantity returned to product.stock_quantity
     *   - An OrderStatusChanged confirmation mail is queued
     */
    public function cancel(Request $request, int $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Csak függő (pending) rendelést tudsz lemondani. '
                           . 'Ha már feldolgozás alatt van, vedd fel velünk a kapcsolatot.',
            ], 422);
        }

        $previousStatus = $order->status;

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock_quantity', $item->quantity);
            }

            $order->update(['status' => 'canceled']);
        });

        try {
            Mail::to($order->user->email)
                ->queue(new OrderStatusChanged(
                    $order->fresh(['items.product', 'address', 'user']),
                    'canceled',
                    $previousStatus
                ));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue order-canceled email', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return new OrderResource($order->fresh(['items.product', 'address']));
    }
}
