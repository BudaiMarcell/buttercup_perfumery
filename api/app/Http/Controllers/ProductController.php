<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Bulk stock check used by the cart drawer right before the user
     * proceeds to checkout. Takes a list of {product_id, quantity}
     * tuples, returns the same list annotated with the live stock and
     * a per-line `in_stock` boolean.
     *
     * Why a single endpoint, not a per-product loop: a cart with N
     * items would otherwise fire N requests; this collapses it to one
     * trip and one DB query.
     *
     * Public on purpose — no auth required, since guests have carts
     * too. Throttled at the route level to keep it cheap.
     */
    public function checkStock(Request $request)
    {
        $validated = $request->validate([
            'items'              => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1|max:1000',
        ]);

        $ids = collect($validated['items'])->pluck('product_id')->all();

        // Single round-trip. We pull only what we need (id + stock +
        // name) so the response doesn't grow with unrelated columns.
        $products = Product::whereIn('id', $ids)
            ->where('is_active', true)
            ->get(['id', 'name', 'stock_quantity'])
            ->keyBy('id');

        $results = collect($validated['items'])->map(function ($line) use ($products) {
            $product = $products->get($line['product_id']);
            if (!$product) {
                return [
                    'product_id' => $line['product_id'],
                    'requested'  => $line['quantity'],
                    'available'  => 0,
                    'in_stock'   => false,
                    'name'       => null,
                    'reason'     => 'unavailable',
                ];
            }
            return [
                'product_id' => $product->id,
                'requested'  => $line['quantity'],
                'available'  => (int) $product->stock_quantity,
                'in_stock'   => $product->stock_quantity >= $line['quantity'],
                'name'       => $product->name,
                'reason'     => $product->stock_quantity >= $line['quantity']
                    ? null
                    : ($product->stock_quantity > 0 ? 'low_stock' : 'out_of_stock'),
            ];
        })->values();

        return response()->json([
            'all_in_stock' => $results->every(fn ($r) => $r['in_stock']),
            'items'        => $results,
        ]);
    }

    public function index(Request $request)
    {

        $validated = $request->validate([
            'category'       => 'sometimes|string|max:120',
            'brand'          => 'sometimes|string|max:120',
            'gender'         => ['sometimes', Rule::in(['male', 'female', 'unisex'])],
            'min_price'      => 'sometimes|numeric|min:0',
            'max_price'      => 'sometimes|numeric|min:0',
            'search'         => 'sometimes|string|max:120',
            'sort_by'        => ['sometimes', Rule::in(['price', 'name', 'created_at'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page'       => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Product::where('is_active', true)
            ->with(['primaryImage', 'category']);

        if (isset($validated['category'])) {
            $query->whereHas('category', function ($q) use ($validated) {
                $q->where('slug', $validated['category']);
            });
        }

        if (isset($validated['brand'])) {
            $query->where('brand', $validated['brand']);
        }

        if (isset($validated['gender'])) {
            $query->where('gender', $validated['gender']);
        }

        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }
        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        if (isset($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                  ->orWhere('brand', 'like', '%' . $validated['search'] . '%');
            });
        }

        $sortBy        = $validated['sort_by']        ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $products = $query->paginate($validated['per_page'] ?? 12);

        return ProductResource::collection($products);
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['images', 'category'])
            ->firstOrFail();

        return new ProductResource($product);
    }
}