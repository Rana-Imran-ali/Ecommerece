<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryLog::with(['product', 'user', 'variant'])->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $logs     = $query->paginate(25)->withQueryString();
        $products = Product::orderBy('name')->with('variants')->get(['id', 'name']);

        $types = [
            'stock_in', 'stock_out', 'sale', 'return',
            'damaged', 'adjustment_in', 'adjustment_out',
        ];

        return view('admin.inventory.index', compact('logs', 'products', 'types'));
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_id'         => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'type'               => 'required|in:stock_in,stock_out,damaged,adjustment_in,adjustment_out',
            'quantity'           => 'required|integer|min:1',
            'notes'              => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $variant = null;

        if (!empty($validated['product_variant_id'])) {
            $variant = ProductVariant::findOrFail($validated['product_variant_id']);

            $increase       = in_array($validated['type'], ['stock_in', 'adjustment_in']);
            $variantBefore  = (int) $variant->stock;
            $variantAfter   = $increase
                ? $variantBefore + $validated['quantity']
                : max(0, $variantBefore - $validated['quantity']);

            $variant->update(['stock' => $variantAfter]);

            InventoryLog::create([
                'product_id'         => $product->id,
                'product_variant_id' => $variant->id,
                'user_id'            => auth()->id(),
                'type'               => $validated['type'],
                'quantity'           => $validated['quantity'],
                'quantity_before'    => $variantBefore,
                'quantity_after'     => $variantAfter,
                'notes'              => $validated['notes'] ?? null,
            ]);

            return back()->with('success', "Variant stock adjusted: {$product->name} ({$variant->sku}) → {$variantAfter} units.");
        }

        // No variant – adjust product base stock
        $before   = (int) $product->stock;
        $increase = in_array($validated['type'], ['stock_in', 'adjustment_in']);
        $after    = $increase
            ? $before + $validated['quantity']
            : max(0, $before - $validated['quantity']);

        $product->update(['stock' => $after]);

        InventoryLog::create([
            'product_id'      => $product->id,
            'user_id'         => auth()->id(),
            'type'            => $validated['type'],
            'quantity'        => $validated['quantity'],
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'notes'           => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Stock adjusted: {$product->name} → {$after} units.");
    }
}
