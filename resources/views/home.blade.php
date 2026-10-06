@extends('layouts.app')

@section('title', 'Welcome to ' . config('app.name', 'EStore') . ' – Premium Online Shopping')

@section('content')
<div class="space-y-14">
    <!-- 1. Hero Showcase Section -->
    <div class="relative bg-gradient-to-br from-navy-975 via-navy-900 to-navy-850 rounded-3xl border border-navy-800 shadow-2xl overflow-hidden">
        <!-- Atmospheric Background Glow Orbs -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center p-8 sm:p-12 lg:p-16">
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-900/60 text-blue-300 border border-blue-700/50 shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    <span>Authentic Quality &amp; Direct Sourcing</span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Elevated Shopping with <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">Uncompromising</span> Quality.
                </h1>

                <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-xl">
                    Explore our curated catalog of electronics, lifestyle, and home goods. Experience encrypted checkout, same-day dispatch, and 24/7 dedicated assistance.
                </p>

                <!-- Search Input Bar -->
                <form action="{{ url('/search') }}" method="GET" class="flex items-center max-w-lg bg-navy-950/80 border border-blue-500/30 rounded-2xl p-1.5 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-500/30 transition-all shadow-inner">
                    <div class="pl-3.5 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="q" placeholder="Search catalog by keyword, brand, or SKU..."
                           class="w-full px-3 py-2.5 text-sm bg-transparent border-none outline-none text-white placeholder-slate-400 focus:ring-0">
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-blue-600/30 shrink-0">
                        Search
                    </button>
                </form>

                <!-- CTAs -->
                <div class="flex flex-wrap items-center gap-3.5 pt-2">
                    <a href="{{ url('/shop') }}" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:brightness-110 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-600/30 transition-all">
                        Explore Full Catalog &rarr;
                    </a>
                    <a href="{{ url('/categories') }}" class="px-6 py-3 bg-white/10 hover:bg-white/15 text-white text-sm font-bold rounded-xl transition-all border border-white/15 backdrop-blur-sm">
                        View Collections
                    </a>
                </div>
            </div>

            <!-- Hero Highlights Card -->
            <div class="lg:col-span-5 bg-navy-950/70 border border-blue-500/20 backdrop-blur-xl rounded-2xl p-6 sm:p-7 space-y-5 shadow-2xl text-white">
                <div class="flex items-center justify-between border-b border-navy-800 pb-3">
                    <span class="text-xs font-bold text-blue-300 uppercase tracking-wider">Buyer Confidence</span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-700/50">Verified Platform</span>
                </div>
                <div class="space-y-4 text-sm">
                    <div class="flex items-start space-x-3.5">
                        <div class="p-2.5 bg-blue-900/40 text-blue-400 rounded-xl shrink-0 mt-0.5 border border-blue-700/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <span class="font-bold text-white">100% Genuine Guaranteed</span>
                            <p class="text-xs text-slate-300 mt-0.5">Authentic goods sourced directly from certified brands</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3.5">
                        <div class="p-2.5 bg-blue-900/40 text-blue-400 rounded-xl shrink-0 mt-0.5 border border-blue-700/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <span class="font-bold text-white">Live Stock Accuracy</span>
                            <p class="text-xs text-slate-300 mt-0.5">Zero overselling with real-time atomic inventory locks</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3.5">
                        <div class="p-2.5 bg-blue-900/40 text-blue-400 rounded-xl shrink-0 mt-0.5 border border-blue-700/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <span class="font-bold text-white">Flexible &amp; Secure Payments</span>
                            <p class="text-xs text-slate-300 mt-0.5">Stripe 3D-Secure, Cards, and Cash on Delivery</p>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-navy-800">
                    <a href="{{ url('/about') }}" class="text-xs font-bold text-blue-400 hover:text-blue-300 flex items-center justify-between transition-colors">
                        <span>Read our quality guarantee &rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Value Props Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-blue-300 transition-all flex items-center space-x-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl shrink-0 border border-blue-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-navy-950">Express Delivery</h3>
                <p class="text-xs text-slate-500 mt-0.5">Fast doorstep fulfillment</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-blue-300 transition-all flex items-center space-x-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl shrink-0 border border-emerald-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-navy-950">Encrypted Payments</h3>
                <p class="text-xs text-slate-500 mt-0.5">256-bit Stripe security</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-blue-300 transition-all flex items-center space-x-4">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl shrink-0 border border-amber-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-navy-950">30-Day Returns</h3>
                <p class="text-xs text-slate-500 mt-0.5">Seamless refunds &amp; claims</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-blue-300 transition-all flex items-center space-x-4">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl shrink-0 border border-indigo-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-navy-950">24/7 Dedicated Care</h3>
                <p class="text-xs text-slate-500 mt-0.5">WhatsApp and live AI chat</p>
            </div>
        </div>
    </div>

    <!-- 3. Popular Categories Section -->
    <div class="space-y-6">
        <div class="flex items-end justify-between border-b border-slate-200 pb-4">
            <div>
                <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Top Collections</span>
                <h2 class="text-2xl font-extrabold text-navy-950 tracking-tight mt-0.5">Featured Categories</h2>
            </div>
            <a href="{{ url('/categories') }}" class="text-xs sm:text-sm font-bold text-blue-700 hover:text-blue-900 transition-colors flex items-center gap-1">
                View All Categories &rarr;
            </a>
        </div>

        <div id="home-categories-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Dynamic categories populated via JS -->
            <div class="col-span-full py-10 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200">
                Loading categories...
            </div>
        </div>
    </div>

    <!-- 4. Featured & New Products Grid -->
    <div class="space-y-6">
        <div class="flex items-end justify-between border-b border-slate-200 pb-4">
            <div>
                <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Handpicked Selection</span>
                <h2 class="text-2xl font-extrabold text-navy-950 tracking-tight mt-0.5">Trending Products</h2>
            </div>
            <a href="{{ url('/shop') }}" class="text-xs sm:text-sm font-bold text-blue-700 hover:text-blue-900 transition-colors flex items-center gap-1">
                Browse Full Catalog &rarr;
            </a>
        </div>

        <!-- Global Alert for Cart/Wishlist actions -->
        <div id="home-alert"></div>

        <div id="home-products-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Dynamic products populated via JS -->
            <div class="col-span-full py-12 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200">
                Loading catalog items...
            </div>
        </div>
    </div>

    <!-- 5. Promotional Banner -->
    <div class="bg-gradient-to-r from-navy-975 via-navy-900 to-blue-950 rounded-3xl text-white p-8 sm:p-12 shadow-2xl border border-blue-900/60 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden">
        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="space-y-3 max-w-xl text-center md:text-left relative z-10">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30 uppercase tracking-wider">
                Special Limited Offer
            </span>
            <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                Unlock Instant Savings At Checkout
            </h3>
            <p class="text-sm text-slate-300 leading-relaxed">
                Enjoy promotional vouchers on qualifying items. Apply eligible coupon codes directly in your shopping cart for immediate discounts.
            </p>
        </div>
        <div class="shrink-0 flex flex-col sm:flex-row gap-3.5 relative z-10 w-full sm:w-auto">
            <a href="{{ url('/shop') }}" class="px-6 py-3.5 bg-white text-navy-950 hover:bg-slate-100 font-extrabold text-sm rounded-xl transition-all text-center shadow-lg">
                Shop Current Sale &rarr;
            </a>
            <a href="{{ url('/contact') }}" class="px-6 py-3.5 bg-white/10 hover:bg-white/15 text-white font-bold text-sm rounded-xl transition-all border border-white/20 text-center backdrop-blur-sm">
                Inquire For Bulk
            </a>
        </div>
    </div>

    <!-- 6. Developer & API Health Bridge -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-slate-100 text-slate-700 rounded-xl">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">API Health &amp; Diagnostics</h3>
                    <div id="health-status" class="flex items-center space-x-2 text-xs text-slate-600 mt-0.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>Verifying connection to <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono">/api/products</code>...</span>
                    </div>
                </div>
            </div>
            <button type="button" onclick="checkApiHealth()" class="text-xs font-bold px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-navy-950 rounded-xl transition-colors">
                Refresh Status
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
            <span class="inline-block w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            <span>Checking endpoint...</span>
        `;

        const res = await apiFetch('/api/products');
        if (res.ok) {
            const count = res.data?.meta?.total ?? res.data?.data?.length ?? 0;
            statusEl.innerHTML = `
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-emerald-700 font-semibold">Online &amp; Operational</span>
                <span class="text-slate-300">|</span>
                <span class="text-slate-600">${count} active item(s) indexed</span>
            `;
        } else {
            statusEl.innerHTML = `
                <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
                <span class="text-rose-700 font-semibold">API Error: ${res.data?.message || 'Server error'}</span>
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
                <div class="col-span-full py-6 text-center text-xs text-slate-400">
                    No categories currently listed.
                </div>
            `;
            return;
        }

        const categories = res.data.data.slice(0, 6);
        container.innerHTML = categories.map(cat => `
            <a href="/products?category_id=${cat.id}"
               class="p-5 bg-white rounded-2xl border border-slate-200/80 hover:border-blue-400 hover:shadow-lg text-center flex flex-col items-center justify-center transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3 group-hover:bg-blue-600 group-hover:text-white transition-all shadow-inner">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <span class="text-xs font-bold text-navy-950 line-clamp-1 group-hover:text-blue-600 transition-colors">${cat.name}</span>
                <span class="text-[11px] text-slate-400 mt-0.5">${cat.products_count ?? 0} item(s)</span>
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
                <div class="col-span-full py-8 text-center text-sm text-rose-600 bg-white rounded-2xl border border-rose-200">
                    Failed to load featured products.
                </div>
            `;
            return;
        }

        const products = res.data?.data || [];
        if (products.length === 0) {
            container.innerHTML = `
                <div class="col-span-full py-12 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200">
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
                ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">In Stock (${product.stock})</span>`
                : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Out of Stock</span>`;

            return `
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden flex flex-col justify-between hover:border-blue-300 hover:shadow-xl transition-all duration-300 group">
                    <div>
                        <!-- Image Container -->
                        <div class="h-52 w-full bg-slate-100 flex items-center justify-center relative overflow-hidden">
                            ${primaryImg 
                                ? `<img src="${primaryImg}" alt="${product.name}" loading="lazy" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">`
                                : `<div class="flex flex-col items-center justify-center text-slate-400">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs">No Preview</span>
                                   </div>`}
                            <div class="absolute top-2.5 right-2.5">${stockBadge}</div>
                        </div>

                        <!-- Info -->
                        <div class="p-5">
                            <div class="text-[11px] text-blue-600 font-bold uppercase tracking-wider mb-1">
                                ${product.category?.name || 'General Catalog'}
                            </div>
                            <h3 class="text-sm font-bold text-navy-950 line-clamp-1 group-hover:text-blue-600 transition-colors" title="${product.name}">
                                <a href="/products/${product.id}">${product.name}</a>
                            </h3>
                            <div class="mt-2.5 text-lg font-extrabold text-navy-950">
                                $${parseFloat(product.price).toFixed(2)}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-5 pt-0 border-t border-slate-100 mt-2 flex flex-col gap-2.5">
                        <div class="flex items-center space-x-2 pt-3">
                            <a href="/products/${product.id}" class="flex-1 text-center py-2 px-3 bg-slate-100 hover:bg-slate-200 text-navy-950 text-xs font-bold rounded-xl transition-all">
                                Details
                            </a>
                            <button type="button" onclick="addToWishlistHome(${product.id})" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl border border-slate-200 transition-all" title="Save to Wishlist">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </button>
                        </div>
                        <button type="button" 
                                id="btn-home-add-${product.id}"
                                onclick="addToCartHome(${product.id})" 
                                ${!inStock ? 'disabled' : ''}
                                class="w-full py-2.5 px-3 text-xs font-bold rounded-xl text-white transition-all shadow-sm ${inStock ? 'bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 shadow-blue-700/20' : 'bg-slate-300 cursor-not-allowed'}">
                            ${inStock ? 'Add to Cart' : 'Out of Stock'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // 4. Cart and Wishlist actions for Home page
    async function addToCartHome(productId) {
        if (!isUserAuthenticated()) {
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
                btn.className = 'w-full py-2.5 px-3 text-xs font-bold rounded-xl text-white bg-emerald-600 transition-all';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                    btn.className = 'w-full py-2.5 px-3 text-xs font-bold rounded-xl text-white bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 shadow-sm transition-all';
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
        if (!isUserAuthenticated()) {
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
