@extends('layouts.app')

@section('title', 'Welcome to ' . config('app.name', 'EStore') . ' – Online Store')

@section('content')
<div class="space-y-12">
    <!-- 1. Hero Showcase Section -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center p-6 sm:p-10 lg:p-12">
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                    <span>✨ Authentic Quality Guaranteed</span>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight">
                    Premium Goods, Transparent Pricing &amp; Fast Delivery.
                </h1>
                <p class="text-base sm:text-lg text-gray-600 leading-relaxed max-w-xl">
                    Discover handpicked collections across electronics, apparel, and lifestyle. Enjoy encrypted payments, 30-day returns, and round-the-clock customer support.
                </p>

                <!-- Search Input Bar -->
                <form action="{{ url('/search') }}" method="GET" class="flex items-center max-w-md bg-gray-50 border border-gray-300 rounded-xl p-1 focus-within:border-indigo-600 focus-within:bg-white focus-within:ring-1 focus-within:ring-indigo-600">
                    <div class="pl-3 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="q" placeholder="Search products, brands, categories..."
                           class="w-full px-3 py-2 text-sm bg-transparent border-none outline-none text-gray-800 placeholder-gray-400">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors shrink-0">
                        Search
                    </button>
                </form>

                <!-- CTAs -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="{{ url('/shop') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        Browse Catalog
                    </a>
                    <a href="{{ url('/categories') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold rounded-lg transition-colors border border-gray-200">
                        View Categories
                    </a>
                </div>
            </div>

            <!-- Hero Highlights Card -->
            <div class="lg:col-span-5 bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Store Highlights</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Verified Seller</span>
                </div>
                <div class="space-y-3.5 text-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-indigo-100 text-indigo-700 rounded-lg shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900">Direct From Verified Suppliers</span>
                            <p class="text-xs text-gray-500 mt-0.5">100% genuine products with manufacturer warranty</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-indigo-100 text-indigo-700 rounded-lg shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900">Real-Time Inventory</span>
                            <p class="text-xs text-gray-500 mt-0.5">Live stock updates prevent out-of-stock cancellations</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-indigo-100 text-indigo-700 rounded-lg shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900">Flexible Payment Options</span>
                            <p class="text-xs text-gray-500 mt-0.5">Pay safely via Stripe, Credit/Debit Cards, or Cash on Delivery</p>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-200">
                    <a href="{{ url('/about') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center justify-between">
                        <span>Learn more about our quality pledge &rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Value Props Bar (No animation, high contrast) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-gray-200 flex items-center space-x-4">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900">Free Shipping</h3>
                <p class="text-xs text-gray-500 mt-0.5">On orders with free delivery voucher</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 flex items-center space-x-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900">Secure Payments</h3>
                <p class="text-xs text-gray-500 mt-0.5">256-bit encrypted checkout flow</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 flex items-center space-x-4">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-lg shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900">Easy Returns</h3>
                <p class="text-xs text-gray-500 mt-0.5">30-day money-back guarantee</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 flex items-center space-x-4">
            <div class="p-3 bg-purple-50 text-purple-600 rounded-lg shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900">24/7 Support</h3>
                <p class="text-xs text-gray-500 mt-0.5">WhatsApp and ticket assistance</p>
            </div>
        </div>
    </div>

    <!-- 3. Popular Categories Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Explore Categories</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Find items organized by collection</p>
            </div>
            <a href="{{ url('/categories') }}" class="text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                View All Categories &rarr;
            </a>
        </div>

        <div id="home-categories-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Dynamic categories populated via JS -->
            <div class="col-span-full py-8 text-center text-sm text-gray-500 bg-white rounded-xl border border-gray-200">
                Loading categories...
            </div>
        </div>
    </div>

    <!-- 4. Featured & New Products Grid -->
    <div class="space-y-4">
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Featured Products</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Trending essentials in stock now</p>
            </div>
            <a href="{{ url('/shop') }}" class="text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                Browse Full Catalog &rarr;
            </a>
        </div>

        <!-- Global Alert for Cart/Wishlist actions -->
        <div id="home-alert"></div>

        <div id="home-products-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Dynamic products populated via JS -->
            <div class="col-span-full py-12 text-center text-sm text-gray-500 bg-white rounded-xl border border-gray-200">
                Loading catalog items...
            </div>
        </div>
    </div>

    <!-- 5. Promotional Banner -->
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 rounded-2xl text-white p-6 sm:p-10 shadow-xs flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2 max-w-xl text-center md:text-left">
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/20 text-white uppercase tracking-wider">
                Limited Time Promo
            </span>
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight">
                Save on your purchase today
            </h3>
            <p class="text-sm text-indigo-100 leading-relaxed">
                Enjoy exclusive discounts on top items. Enter available coupon codes during checkout to claim your savings instantly.
            </p>
        </div>
        <div class="shrink-0 flex flex-col sm:flex-row gap-3">
            <a href="{{ url('/shop') }}" class="px-6 py-3 bg-white text-indigo-900 hover:bg-indigo-50 font-bold text-sm rounded-xl transition-colors text-center shadow-xs">
                Shop The Sale
            </a>
            <a href="{{ url('/contact') }}" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold text-sm rounded-xl transition-colors border border-white/20 text-center">
                Contact For Bulk Orders
            </a>
        </div>
    </div>

    <!-- 6. Developer & API Health Bridge (Preserves 100% backend health check) -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="p-1.5 bg-gray-100 text-gray-600 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">API Status &amp; Diagnostics</h3>
                    <div id="health-status" class="flex items-center space-x-2 text-xs text-gray-600 mt-0.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>Verifying connection to <code class="bg-gray-100 px-1 py-0.5 rounded text-gray-700">/api/products</code>...</span>
                    </div>
                </div>
            </div>
            <button type="button" onclick="checkApiHealth()" class="text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                Re-check API
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 1. API Health Check (preserved intact)
    async function checkApiHealth() {
        const statusEl = document.getElementById('health-status');
        if (!statusEl) return;
        statusEl.innerHTML = `
            <span class="inline-block w-2 h-2 rounded-full bg-amber-400"></span>
            <span>Checking endpoint...</span>
        `;

        const res = await apiFetch('/api/products');
        if (res.ok) {
            const count = res.data?.meta?.total ?? res.data?.data?.length ?? 0;
            statusEl.innerHTML = `
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-emerald-700 font-semibold">Online &amp; Healthy</span>
                <span class="text-gray-300">|</span>
                <span class="text-gray-600">${count} active product(s) available</span>
            `;
        } else {
            statusEl.innerHTML = `
                <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span>
                <span class="text-red-700 font-semibold">API Error: ${res.data?.message || 'Server error'}</span>
            `;
        }
    }

    // 2. Load Categories for Home Grid
    async function loadHomeCategories() {
        const container = document.getElementById('home-categories-grid');
        if (!container) return;

        const res = await apiFetch('/api/categories');
        if (!res.ok || !Array.isArray(res.data?.data) || res.data.data.length === 0) {
            container.innerHTML = `
                <div class="col-span-full py-4 text-center text-xs text-gray-500">
                    No categories currently listed.
                </div>
            `;
            return;
        }

        const categories = res.data.data.slice(0, 6);
        container.innerHTML = categories.map(cat => `
            <a href="/products?category_id=${cat.id}"
               class="p-4 bg-white rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-indigo-50/20 text-center flex flex-col items-center justify-center transition-colors group">
                <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-900 line-clamp-1 group-hover:text-indigo-600">${cat.name}</span>
                <span class="text-[11px] text-gray-500 mt-0.5">${cat.products_count ?? 0} item(s)</span>
            </a>
        `).join('');
    }

    // 3. Load Featured Products for Home Grid
    async function loadHomeProducts() {
        const container = document.getElementById('home-products-grid');
        if (!container) return;

        const res = await apiFetch('/api/products?per_page=8');
        if (!res.ok) {
            container.innerHTML = `
                <div class="col-span-full py-8 text-center text-sm text-red-600 bg-white rounded-xl border border-red-200">
                    Failed to load featured products.
                </div>
            `;
            return;
        }

        const products = res.data?.data || [];
        if (products.length === 0) {
            container.innerHTML = `
                <div class="col-span-full py-12 text-center text-sm text-gray-500 bg-white rounded-xl border border-gray-200">
                    No products currently available in the catalog.
                </div>
            `;
            return;
        }

        container.innerHTML = products.map(product => {
            const primaryImg = product.primary_image?.image 
                ? `/storage/${product.primary_image.image}` 
                : null;
            
            const inStock = (product.stock ?? 0) > 0;
            const stockBadge = inStock
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800">In Stock (${product.stock})</span>`
                : `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-100 text-red-800">Out of Stock</span>`;

            return `
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between hover:border-gray-300 shadow-xs">
                    <div>
                        <!-- Image Container -->
                        <div class="h-48 w-full bg-gray-100 flex items-center justify-center relative overflow-hidden">
                            ${primaryImg 
                                ? `<img src="${primaryImg}" alt="${product.name}" loading="lazy" class="h-full w-full object-cover">`
                                : `<span class="text-xs text-gray-400">No Image</span>`}
                            <div class="absolute top-2 right-2">${stockBadge}</div>
                        </div>

                        <!-- Info -->
                        <div class="p-4">
                            <div class="text-[11px] text-indigo-600 font-semibold uppercase tracking-wider mb-1">
                                ${product.category?.name || 'General'}
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 line-clamp-1" title="${product.name}">
                                <a href="/products/${product.id}" class="hover:text-indigo-600 transition-colors">${product.name}</a>
                            </h3>
                            <div class="mt-2 text-base font-extrabold text-gray-900">
                                $${parseFloat(product.price).toFixed(2)}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 pt-0 border-t border-gray-100 mt-2 flex flex-col gap-2">
                        <div class="flex items-center space-x-2 pt-2">
                            <a href="/products/${product.id}" class="flex-1 text-center py-1.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold rounded-md transition-colors">
                                View Details
                            </a>
                            <button type="button" onclick="addToWishlistHome(${product.id})" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-md border border-gray-200 transition-colors" title="Save to Wishlist">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </button>
                        </div>
                        <button type="button" 
                                id="btn-home-add-${product.id}"
                                onclick="addToCartHome(${product.id})" 
                                ${!inStock ? 'disabled' : ''}
                                class="w-full py-2 px-3 text-xs font-bold rounded-md text-white transition-colors ${inStock ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 cursor-not-allowed'}">
                            ${inStock ? 'Add to Cart' : 'Out of Stock'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // 4. Cart and Wishlist actions for Home page
    async function addToCartHome(productId) {
        if (!getAuthToken()) {
            showAlert('home-alert', 'Please sign in to add items to your cart.', 'warning');
            setTimeout(() => { window.location.href = '/login?redirect=/'; }, 1000);
            return;
        }

        const btn = document.getElementById(`btn-home-add-${productId}`);
        const originalText = btn ? btn.textContent : 'Add to Cart';
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Adding...';
        }

        const res = await apiFetch('/api/cart/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });

        if (res.ok) {
            showAlert('home-alert', 'Product added to your cart!', 'success');
            if (btn) {
                btn.textContent = '✓ Added to Cart';
                btn.className = 'w-full py-2 px-3 text-xs font-bold rounded-md text-white bg-emerald-600 transition-colors';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                    btn.className = 'w-full py-2 px-3 text-xs font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors';
                }, 1500);
            }
            if (typeof fetchNavbarCounts === 'function') fetchNavbarCounts();
        } else {
            showAlert('home-alert', res.data?.message || 'Could not add product to cart.', 'danger');
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
    }

    async function addToWishlistHome(productId) {
        if (!getAuthToken()) {
            showAlert('home-alert', 'Please sign in to save items to your wishlist.', 'warning');
            setTimeout(() => { window.location.href = '/login?redirect=/'; }, 1000);
            return;
        }

        const res = await apiFetch('/api/wishlist/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId })
        });

        if (res.ok) {
            showAlert('home-alert', 'Product saved to your wishlist!', 'success');
            if (typeof fetchNavbarCounts === 'function') fetchNavbarCounts();
        } else {
            showAlert('home-alert', res.data?.message || 'Item already in wishlist or unavailable.', 'info');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkApiHealth();
        loadHomeCategories();
        loadHomeProducts();
    });
</script>
@endpush
