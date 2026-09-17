<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\StripeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Frontend Routes (Testing UI)
|--------------------------------------------------------------------------
*/

// Home & Shop
Route::view('/', 'home')->name('home');
Route::view('/shop', 'products.index')->name('shop');

// Products List & Details
Route::view('/products', 'products.index')->name('products.index');
Route::get('/products/{id}', function ($id) {
    return view('products.show', ['productId' => $id]);
})->name('products.show');

// Search
Route::get('/search', function (Request $request) {
    return view('search', ['query' => $request->get('q', '')]);
})->name('search');

// Categories
Route::view('/categories', 'categories.index')->name('categories.index');

// About & Contact
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::post('/contact', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string|max:2000',
    ]);

    \App\Models\ContactInquiry::create([
        'name'    => $validated['name'],
        'email'   => $validated['email'],
        'subject' => $validated['subject'],
        'message' => $validated['message'],
        'status'  => 'unread',
    ]);

    return back()->with('success', 'Thank you for reaching out! Your message has been received and our team will get back to you shortly.');
})->middleware('throttle:15,1')->name('contact.submit');

// Shopping Cart, Wishlist, Addresses, Profile & Checkout (Client-Side Token / Hybrid Views)
Route::view('/cart', 'cart.index')->name('cart.index');
Route::view('/checkout', 'checkout.index')->name('checkout.index');
Route::view('/wishlist', 'wishlist.index')->name('wishlist.index');
Route::view('/addresses', 'addresses.index')->name('addresses.index');
Route::get('/profile', function () {
    return redirect()->to('/account#profile');
})->name('profile.edit');

// Dashboard redirect bridge (redirects admin to admin dashboard, customer to My Account)
Route::get('/dashboard', function () {
    if (auth()->user()?->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('account.index');
})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Customer Account Routes (requires authentication)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Unified My Account hub
    Route::view('/account', 'account.index')->name('account.index');

    // Orders History & Details
    Route::view('/orders', 'orders.index')->name('orders.index');
    Route::get('/orders/{id}', function ($id) {
        return view('orders.show', ['orderId' => $id]);
    })->name('orders.show');
});

/*
|--------------------------------------------------------------------------
| Stripe Payment Redirect Pages & Webhook
|--------------------------------------------------------------------------
*/

// Shown after Stripe redirects user back (success/cancel)
Route::get('/payment/success', [StripeController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [StripeController::class, 'cancel'])->name('payment.cancel');

// Stripe webhook – no auth, no CSRF (excluded in bootstrap/app.php)
Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook');

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
    Route::post('orders/{order}/notify/approval', [AdminOrderController::class, 'sendApprovalEmail'])->name('orders.notify.approval');
    Route::post('orders/{order}/notify/delivery-date', [AdminOrderController::class, 'sendDeliveryDateEmail'])->name('orders.notify.delivery-date');

    // Users & Customers
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // Payments
    Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::patch('payments/{payment}/status', [AdminPaymentController::class, 'updateStatus'])->name('payments.status');

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
