<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\ApiAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Catalog, Auth & Coupons)
|--------------------------------------------------------------------------
*/

// Authentication (Rate limited)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
});

// Products (Public read routes)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/{product}', [ProductController::class, 'show'])->name('api.products.show');
    Route::post('/{product}/check-stock', [ProductController::class, 'validateStock'])->name('api.products.stock.validate');

    // Reviews (Public list)
    Route::get('/{product}/reviews', [ReviewController::class, 'index'])->name('api.products.reviews.index');
});

// Categories (Public read routes)
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::get('/{category}', [CategoryController::class, 'show'])->name('api.categories.show');
    Route::get('/{category}/products', [CategoryController::class, 'products'])->name('api.categories.products');
});

// Coupons (Public Validation & Listing)
Route::prefix('coupons')->group(function () {
    Route::get('/', [CouponController::class, 'index'])->name('api.coupons.index');
    Route::post('/validate', [CouponController::class, 'validateCoupon'])->name('api.coupons.validate');
});

/*
|--------------------------------------------------------------------------
| Admin Catalog Management Routes (Requires Admin privileges)
|--------------------------------------------------------------------------
*/
Route::middleware([ApiAuthMiddleware::class, 'admin'])->group(function () {
    // Products Admin API
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store'])->name('api.products.store');
        Route::put('/{product}', [ProductController::class, 'update'])->name('api.products.update');
        Route::patch('/{product}', [ProductController::class, 'update'])->name('api.products.patch');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('api.products.destroy');
        Route::post('/{product}/images', [ProductController::class, 'uploadImages'])->name('api.products.images.upload');
        Route::put('/{product}/images/{image}/primary', [ProductController::class, 'setPrimaryImage'])->name('api.products.images.primary');
        Route::delete('/{product}/images/{image}', [ProductController::class, 'deleteImage'])->name('api.products.images.delete');
        Route::patch('/{product}/stock', [ProductController::class, 'updateStock'])->name('api.products.stock.update');
    });

    // Categories Admin API
    Route::prefix('categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->name('api.categories.store');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Bearer Token or Active Session)
|--------------------------------------------------------------------------
*/
Route::middleware(ApiAuthMiddleware::class)->group(function () {
    // Auth Profile & Security
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    // POST used (not PUT) so multipart/form-data avatar uploads are supported
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('api.profile.update');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('api.password.update');
    Route::delete('/account', [AuthController::class, 'deleteAccount'])->name('api.account.delete');

    // Reviews (Submit & Delete)
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('api.products.reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('api.reviews.destroy');

    // Shopping Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('api.cart.index');
        Route::post('/items', [CartController::class, 'add'])->name('api.cart.add');
        Route::put('/items/{cartItem}', [CartController::class, 'update'])->name('api.cart.update');
        Route::delete('/items/{cartItem}', [CartController::class, 'remove'])->name('api.cart.remove');
        Route::delete('/', [CartController::class, 'clear'])->name('api.cart.clear');
    });

    // Wishlist
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('api.wishlist.index');
        Route::post('/items', [WishlistController::class, 'add'])->name('api.wishlist.add');
        Route::delete('/items/{wishlistItem}', [WishlistController::class, 'remove'])->name('api.wishlist.remove');
        Route::delete('/', [WishlistController::class, 'clear'])->name('api.wishlist.clear');
    });

    // Addresses
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index'])->name('api.addresses.index');
        Route::post('/', [AddressController::class, 'store'])->name('api.addresses.store');
        Route::put('/{address}', [AddressController::class, 'update'])->name('api.addresses.update');
        Route::delete('/{address}', [AddressController::class, 'destroy'])->name('api.addresses.destroy');
        Route::patch('/{address}/default', [AddressController::class, 'setDefault'])->name('api.addresses.default');
    });

    // Orders & Checkout
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('api.orders.index');
        Route::post('/', [OrderController::class, 'store'])->name('api.orders.store'); // COD / Bank Transfer checkout
        Route::get('/{order}', [OrderController::class, 'show'])->name('api.orders.show');
        Route::patch('/{order}/cancel', [OrderController::class, 'cancel'])->name('api.orders.cancel');
    });

    // Stripe – Card Payment
    Route::post('/stripe/payment-intent', [StripeController::class, 'createPaymentIntent'])
        ->name('api.stripe.payment-intent'); // Step 1: Create PaymentIntent, returns client_secret
    Route::post('/stripe/create-session', [StripeController::class, 'createSession'])
        ->name('api.stripe.session'); // Alternative: Hosted Stripe Checkout
});

