<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckStockRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Requests\ValidateStockRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a paginated listing of products with search, category filtering,
     * price range, stock status, and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with([
            'category:id,name',
            'primaryImage:id,product_id,image',
        ]);

        // Search by product name or description
        if ($request->filled('search')) {
            $searchTerm = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        // Filter by stock availability
        if ($request->has('in_stock')) {
            $inStock = filter_var($request->input('in_stock'), FILTER_VALIDATE_BOOLEAN);
            if ($inStock) {
                $query->where('stock', '>', 0);
            } else {
                $query->where('stock', '<=', 0);
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'latest');
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'stock_asc' => $query->orderBy('stock', 'asc'),
            'stock_desc' => $query->orderBy('stock', 'desc'),
            default => $query->latest('id'),
        };

        $perPage = min((int) $request->input('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created product in storage, with optional initial image uploads.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($request, $validated) {
            // Create product record
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'stock' => $validated['stock'],
            ]);

            // Handle image uploads if present
            if ($request->hasFile('images')) {
                $primaryIndex = (int) $request->input('primary_image_index', 0);

                foreach ($request->file('images') as $index => $uploadedFile) {
                    $path = $uploadedFile->store('products', 'public');

                    $product->images()->create([
                        'image' => $path,
                        'is_primary' => ($index === $primaryIndex),
                    ]);
                }
            }

            return $product;
        });

        $product->load(['category', 'images']);

        Cache::forget('categories.all');

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $product,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified product details with category and images.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'images']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'category' => $product->category,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'in_stock' => $product->stock > 0,
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'product_id' => $image->product_id,
                        'image' => $image->image,
                        'url' => '/storage/' . ltrim($image->image, '/'),
                        'is_primary' => (bool) $image->is_primary,
                        'created_at' => $image->created_at,
                    ];
                }),
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        $product->load(['category', 'images']);

        Cache::forget('categories.all');

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified product (soft-delete, preserving images for order history).
     */
   public function destroy(Product $product): JsonResponse
{
    foreach ($product->images as $image) {
        Storage::disk('public')->delete($image->image);
        $image->delete();
    }

    $product->delete();

    Cache::forget('categories.all');

    return response()->json([
        'success' => true,
        'message' => 'Product deleted successfully.',
    ], Response::HTTP_OK);
}
    /**
     * Upload additional images for an existing product.
     */
    public function uploadImages(UploadProductImageRequest $request, Product $product): JsonResponse
    {
        $uploadedImages = [];
        $hasExistingPrimary = $product->images()->where('is_primary', true)->exists();

        $files = $request->file('images') ?? ($request->hasFile('image') ? [$request->file('image')] : []);
        $setAsPrimary = filter_var($request->input('is_primary', false), FILTER_VALIDATE_BOOLEAN);

        DB::transaction(function () use ($files, $product, $setAsPrimary, $hasExistingPrimary, &$uploadedImages) {
            // If new upload should be primary, clear current primary flag
            if ($setAsPrimary) {
                $product->images()->update(['is_primary' => false]);
            }

            foreach ($files as $index => $file) {
                $path = $file->store('products', 'public');
                $isPrimary = $setAsPrimary ? ($index === 0) : (!$hasExistingPrimary && $index === 0);

                $image = $product->images()->create([
                    'image' => $path,
                    'is_primary' => $isPrimary,
                ]);

                if ($isPrimary) {
                    $hasExistingPrimary = true;
                }

                $uploadedImages[] = [
                    'id' => $image->id,
                    'image' => $image->image,
                    'url' => Storage::disk('public')->url($image->image),
                    'is_primary' => (bool) $image->is_primary,
                ];
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($uploadedImages) . ' image(s) uploaded successfully.',
            'data' => $uploadedImages,
        ], Response::HTTP_CREATED);
    }

    /**
     * Set a specific image as the primary image for a product.
     */
    public function setPrimaryImage(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'The specified image does not belong to this product.',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($product, $image) {
            $product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Primary image updated successfully.',
            'data' => [
                'id' => $image->id,
                'product_id' => $image->product_id,
                'image' => $image->image,
                'url' => Storage::disk('public')->url($image->image),
                'is_primary' => true,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Delete a specific image of a product and designate a new primary if needed.
     */
    public function deleteImage(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'The specified image does not belong to this product.',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($product, $image) {
            $wasPrimary = (bool) $image->is_primary;

            // Remove file from disk
            if ($image->image && Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }

            // Remove record
            $image->delete();

            // If the deleted image was primary, make the first remaining image primary
            if ($wasPrimary) {
                $nextPrimary = $product->images()->first();
                if ($nextPrimary) {
                    $nextPrimary->update(['is_primary' => true]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Product image deleted successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Validate product stock availability against a requested quantity.
     */
    public function validateStock(ValidateStockRequest $request, Product $product): JsonResponse
    {
        $requestedQuantity = (int) $request->validated('quantity');
        $availableStock = (int) $product->stock;
        $isAvailable = $availableStock >= $requestedQuantity;

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'requested_quantity' => $requestedQuantity,
                'available_stock' => $availableStock,
                'is_available' => $isAvailable,
                'message' => $isAvailable
                    ? 'Requested quantity is available.'
                    : "Insufficient stock. Only {$availableStock} item(s) available.",
            ],
        ], $isAvailable ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Update or adjust product stock quantity safely using database row locking.
     */
    public function updateStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:set,increment,decrement'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $updatedStock = DB::transaction(function () use ($product, $validated) {
            // Lock the product row for update to prevent race conditions
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first();

            $action = $validated['action'];
            $amount = (int) $validated['amount'];

            $newStock = match ($action) {
                'set' => $amount,
                'increment' => $lockedProduct->stock + $amount,
                'decrement' => $lockedProduct->stock - $amount,
            };

            if ($newStock < 0) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, "Stock cannot be reduced below 0. Current stock is {$lockedProduct->stock}.");
            }

            $lockedProduct->update(['stock' => $newStock]);

            return $newStock;
        });

        return response()->json([
            'success' => true,
            'message' => 'Product stock updated successfully.',
            'data' => [
                'product_id' => $product->id,
                'stock' => $updatedStock,
                'in_stock' => $updatedStock > 0,
            ],
        ], Response::HTTP_OK);
    }
}
