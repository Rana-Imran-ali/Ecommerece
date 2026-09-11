<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'primaryImage'])
            ->withCount('reviews');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('stock_filter')) {
            match ($request->stock_filter) {
                'low'  => $query->where('stock', '>', 0)->where('stock', '<=', 10),
                'out'  => $query->where('stock', 0),
                default => null,
            };
        }

        $products   = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'status'      => 'required|in:active,inactive',
            'images.*'    => 'nullable|image|max:2048',
        ]);

        $product = Product::create($validated);

        // Inventory log for initial stock
        if ($product->stock > 0) {
            InventoryLog::create([
                'product_id'      => $product->id,
                'user_id'         => auth()->id(),
                'type'            => 'stock_in',
                'quantity'        => $product->stock,
                'quantity_before' => 0,
                'quantity_after'  => $product->stock,
                'notes'           => 'Initial stock on product creation',
            ]);
        }

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image'      => $path,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" created successfully.");
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $product->load(['images', 'inventoryLogs' => fn($q) => $q->with('user')->take(20)]);
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'status'      => 'required|in:active,inactive',
            'images.*'    => 'nullable|image|max:2048',
        ]);

        $oldStock = $product->stock;
        $product->update($validated);

        // Log stock adjustment if stock changed
        if ($oldStock !== (int) $validated['stock']) {
            $diff = (int) $validated['stock'] - $oldStock;
            InventoryLog::create([
                'product_id'      => $product->id,
                'user_id'         => auth()->id(),
                'type'            => $diff > 0 ? 'adjustment_in' : 'adjustment_out',
                'quantity'        => abs($diff),
                'quantity_before' => $oldStock,
                'quantity_after'  => (int) $validated['stock'],
                'notes'           => 'Manual stock adjustment via admin panel',
            ]);
        }

        // Handle new image uploads
        if ($request->hasFile('images')) {
            $hasPrimary = $product->images()->where('is_primary', true)->exists();
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image'      => $path,
                    'is_primary' => !$hasPrimary && $index === 0,
                ]);
                $hasPrimary = true;
            }
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "Product \"{$product->name}\" updated successfully.");
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function deleteImage(Product $product, ProductImage $image)
    {
        Storage::disk('public')->delete($image->image);

        // If deleted image was primary, promote next image
        if ($image->is_primary) {
            $next = $product->images()->where('id', '!=', $image->id)->first();
            $next?->update(['is_primary' => true]);
        }
        $image->delete();

        return back()->with('success', 'Image deleted.');
    }

    public function setPrimaryImage(Product $product, ProductImage $image)
    {
        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Primary image updated.');
    }
}
