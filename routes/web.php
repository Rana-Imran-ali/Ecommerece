<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Frontend Routes (Testing UI)
|--------------------------------------------------------------------------
*/

// Home
Route::view('/', 'home')->name('home');

// Products List & Details
Route::view('/products', 'products.index')->name('products.index');
Route::get('/products/{id}', function ($id) {
    return view('products.show', ['productId' => $id]);
})->name('products.show');

// Categories
Route::view('/categories', 'categories.index')->name('categories.index');

// Shopping Cart & Checkout Flow
Route::view('/cart', 'cart.index')->name('cart.index');
Route::view('/checkout', 'checkout.index')->name('checkout.index');

// Orders History & Details
Route::view('/orders', 'orders.index')->name('orders.index');
Route::get('/orders/{id}', function ($id) {
    return view('orders.show', ['orderId' => $id]);
})->name('orders.show');

// Wishlist
Route::view('/wishlist', 'wishlist.index')->name('wishlist.index');

// Addresses
Route::view('/addresses', 'addresses.index')->name('addresses.index');

// Dashboard redirect bridge (redirects admin to admin dashboard, customer to home)
Route::get('/dashboard', function () {
    if (auth()->user()?->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

// Profile & Security
Route::view('/profile', 'profile.index')->name('profile.edit');

/*
|--------------------------------------------------------------------------
| Admin Routes – Protected by AdminMiddleware (auth + admin role)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'deleteImage'])
        ->name('products.images.delete');
    Route::patch('products/{product}/images/{image}/primary', [AdminProductController::class, 'setPrimaryImage'])
        ->name('products.images.primary');

    // Categories
    Route::resource('categories', AdminCategoryController::class)->except(['show']);

    // Inventory
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Orders
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

    // Customers
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // Coupons
    Route::resource('coupons', AdminCouponController::class)->except(['show']);

    // Reviews
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/top-products', [ReportController::class, 'topProducts'])->name('reports.top-products');
});

require __DIR__.'/auth.php';
