<?php

namespace App\Http\Controllers;

use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{

    public function index(Request $request)
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with(['product.primaryImage', 'product.category'])
            ->orderByDesc('created_at')
            ->get();

        return WishlistResource::collection($items);
    }

    public function store(Request $request, int $product)
    {

        $productModel = Product::where('id', $product)
            ->where('is_active', true)
            ->firstOrFail();

        $entry = Wishlist::firstOrCreate([
            'user_id'    => $request->user()->id,
            'product_id' => $productModel->id,
        ]);

        $entry->load(['product.primaryImage', 'product.category']);

        $status = $entry->wasRecentlyCreated ? 201 : 200;

        return (new WishlistResource($entry))
            ->response()
            ->setStatusCode($status);
    }

    public function destroy(Request $request, int $product)
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $product)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist.']);
    }
}
