<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{
    /**
     * Display all customer reviews for a given product.
     */
    public function index(Product $product): JsonResponse
    {
        $reviews = Review::where('product_id', $product->id)
            ->where('status', 'approved')
            ->with('user:id,name')
            ->latest('id')
            ->get();

        $averageRating = $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'total_reviews' => $reviews->count(),
                'average_rating' => $averageRating,
                'reviews' => $reviews,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store or update a customer review for a product.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = $request->user()->id;

        $review = Review::updateOrCreate(
            [
                'user_id' => $userId,
                'product_id' => $product->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'status' => 'pending',
            ]
        );

        $review->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully and will appear once approved by a moderator.',
            'data' => $review,
        ], Response::HTTP_CREATED);
    }

    /**
     * Delete a review submitted by the authenticated user.
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this review.',
            ], Response::HTTP_FORBIDDEN);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ], Response::HTTP_OK);
    }
}
