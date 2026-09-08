<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with product counts.
     */
    public function index(): JsonResponse
    {
        $categories = Cache::remember('categories.all', 300, function () {
            return Category::withCount('products')
                ->latest('id')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $category = Category::create($validated);

        Cache::forget('categories.all');

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $category,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified category with its products.
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['products' => function ($q) {
            $q->with('primaryImage')->latest('id');
        }]);

        return response()->json([
            'success' => true,
            'data' => $category,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
        ]);

        $category->update($validated);

        Cache::forget('categories.all');

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $category,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a category that has products.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category->delete();

        Cache::forget('categories.all');

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Display all products in the specified category.
     */
    public function products(Category $category): JsonResponse
    {
        $products = $category->products()
            ->with(['primaryImage'])
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'category' => $category,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], Response::HTTP_OK);
    }
}
