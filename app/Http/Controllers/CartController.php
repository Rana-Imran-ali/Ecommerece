<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    /**
     * Display the current user's cart with items, product info, and totals.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $items = CartItem::where('cart_id', $cart->id)
            ->with([
                'product.primaryImage',
                'product.category',
                'variant.optionValues.option',
            ])
            ->get();

        $subtotal = $items->sum(function ($item) {
            $unitPrice = $item->variant ? $item->variant->effective_price : ($item->product ? (float) $item->product->price : 0);
            return $unitPrice * $item->quantity;
        });

        $totalItems = $items->sum('quantity');

        return response()->json([
            'success' => true,
            'data' => [
                'cart_id' => $cart->id,
                'items' => $items,
                'subtotal' => round($subtotal, 2),
                'total_items' => $totalItems,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Add a product to the cart with stock validation.
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity'           => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $variant = null;

        if (!empty($validated['product_variant_id'])) {
            $variant = ProductVariant::where('id', $validated['product_variant_id'])
                ->where('product_id', $product->id)
                ->firstOrFail();

            $availableStock = (int) $variant->stock;
        } else {
            $availableStock = (int) $product->stock;
        }

        if ($availableStock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock. Only {$availableStock} item(s) available.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $itemQuery = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id);

        if ($variant) {
            $itemQuery->where('product_variant_id', $variant->id);
        } else {
            $itemQuery->whereNull('product_variant_id');
        }

        $item = $itemQuery->first();

        if ($item) {
            $newQuantity = $item->quantity + $validated['quantity'];

            if ($newQuantity > $availableStock) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot add more. Only {$availableStock} item(s) in stock (already have {$item->quantity} in cart).",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $item->update(['quantity' => $newQuantity]);
        } else {
            $item = CartItem::create([
                'cart_id'            => $cart->id,
                'product_id'         => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity'           => $validated['quantity'],
            ]);
        }

        $item->load(['product.primaryImage', 'variant.optionValues.option']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'data' => $item,
        ], Response::HTTP_OK);
    }

    /**
     * Update quantity of an item in the cart.
     */
    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::where('id', $cartItem->cart_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $cartItem->load(['product', 'variant']);

        $availableStock = $cartItem->variant ? (int) $cartItem->variant->stock : (int) $cartItem->product->stock;

        if ($validated['quantity'] > $availableStock) {
            return response()->json([
                'success' => false,
                'message' => "Requested quantity exceeds available stock ({$availableStock}).",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);
        $cartItem->load(['product.primaryImage', 'variant.optionValues.option']);

        return response()->json([
            'success' => true,
            'message' => 'Cart quantity updated successfully.',
            'data' => $cartItem,
        ], Response::HTTP_OK);
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = Cart::where('id', $cartItem->cart_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
        ], Response::HTTP_OK);
    }

    /**
     * Clear all items in the cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
        ], Response::HTTP_OK);
    }
}
