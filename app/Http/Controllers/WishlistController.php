<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WishlistController extends Controller
{
    /**
     * Display the current user's wishlist items.
     */
    public function index(Request $request): JsonResponse
    {
        $wishlist = Wishlist::firstOrCreate(['user_id' => $request->user()->id]);

        $items = WishlistItem::where('wishlist_id', $wishlist->id)
            ->with(['product.primaryImage', 'product.category', 'variant.optionValues.option'])
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'wishlist_id' => $wishlist->id,
                'items' => $items,
                'total_items' => $items->count(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Add a product to the wishlist.
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $wishlist = Wishlist::firstOrCreate(['user_id' => $request->user()->id]);

        $item = WishlistItem::firstOrCreate([
            'wishlist_id'        => $wishlist->id,
            'product_id'         => $product->id,
            'product_variant_id' => $validated['product_variant_id'] ?? null,
        ]);

        $item->load(['product.primaryImage']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist.',
            'data' => $item,
        ], Response::HTTP_OK);
    }

    /**
     * Remove an item from the wishlist.
     */
    public function remove(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        $wishlist = Wishlist::where('id', $wishlistItem->wishlist_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $wishlistItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from wishlist.',
        ], Response::HTTP_OK);
    }

    /**
     * Clear the entire wishlist.
     */
    public function clear(Request $request): JsonResponse
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)->first();

        if ($wishlist) {
            WishlistItem::where('wishlist_id', $wishlist->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Wishlist cleared successfully.',
        ], Response::HTTP_OK);
    }
}
