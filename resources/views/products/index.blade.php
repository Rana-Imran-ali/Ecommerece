@extends('layouts.app')

@section('title', 'Products - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Catalog &amp; Inventory</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy-950 tracking-tight mt-0.5">Explore Products</h1>
            <p class="text-sm text-slate-500 mt-1">Live inventory with instant search, category filters, and secure ordering.</p>
        </div>
        <div id="product-count" class="text-xs font-bold text-slate-600 bg-white px-3.5 py-1.5 rounded-full border border-slate-200 shadow-2xs self-start sm:self-auto">
            Loading products...
        </div>
    </div>

    <!-- Alert Container -->
    <div id="product-alert"></div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
        <form id="filter-form" onsubmit="applyFilters(event)" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <!-- Search Input -->
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-1.5">Search Catalog</label>
                <div class="relative">
                    <input type="text" id="search-input"
                           oninput="handleSearchDebounce()"
                           class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-slate-400"
                           placeholder="Search by title, keywords, or SKU...">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-1.5">Category</label>
                <select id="category-filter" onchange="changeFilter()"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                    <option value="">All Categories</option>
                </select>
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-1.5">Sort Order</label>
                <select id="sort-filter" onchange="changeFilter()"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                    <option value="latest">Newest First</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A - Z</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2 pt-1 sm:pt-0">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-blue-700/20">
                    Apply Filter
                </button>
                <button type="button" onclick="resetFilters()" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <div id="products-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Skeleton Loaders injected here -->
    </div>

    <!-- Pagination Controls -->
    <div id="pagination-container" class="hidden bg-white p-5 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xs">
        <div class="flex items-center space-x-3 text-xs font-semibold text-slate-600">
            <span id="pagination-summary">Showing 1-12 of 0 products</span>
            <span class="text-slate-300">|</span>
            <div class="flex items-center space-x-2">
                <label for="per-page-select" class="text-slate-500 font-bold uppercase text-[11px]">Per page:</label>
                <select id="per-page-select" onchange="changePerPage(this.value)" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1 bg-slate-50 outline-none focus:ring-1 focus:ring-blue-500 font-semibold">
                    <option value="12">12</option>
                    <option value="24">24</option>
                    <option value="48">48</option>
                </select>
            </div>
        </div>

        <div class="flex items-center space-x-1.5" id="pagination-buttons">
            <!-- Buttons injected dynamically -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentCategory = new URLSearchParams(window.location.search).get('category_id') || '';
    let currentPage = 1;
    let perPage = 12;
    let totalPages = 1;
    let categoriesLoaded = false;

    // Render skeleton placeholders
    function renderSkeletons(count = 8) {
        const container = document.getElementById('products-container');
        container.innerHTML = Array.from({ length: count }).map(() => `
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col justify-between animate-pulse">
                <div>
                    <div class="h-52 w-full bg-slate-100 flex items-center justify-center text-xs text-slate-400">Loading...</div>
                    <div class="p-5 space-y-2.5">
                        <div class="h-3 bg-slate-200 rounded w-1/3"></div>
                        <div class="h-4 bg-slate-200 rounded w-3/4"></div>
                        <div class="h-5 bg-slate-200 rounded w-1/4 mt-2"></div>
                    </div>
                </div>
                <div class="p-5 pt-0 border-t border-slate-100 mt-2 space-y-2.5">
                    <div class="flex space-x-2 pt-2">
                        <div class="h-8 bg-slate-100 rounded-xl flex-1"></div>
                        <div class="h-8 w-9 bg-slate-100 rounded-xl"></div>
                    </div>
                    <div class="h-9 bg-slate-100 rounded-xl w-full"></div>
                </div>
            </div>
        `).join('');
    }

    async function loadCategories() {
        if (categoriesLoaded) return;
        const select = document.getElementById('category-filter');
        const res = await apiFetch('/api/categories');
        if (res.ok && Array.isArray(res.data?.data)) {
            select.innerHTML = '<option value="">All Categories</option>';
            res.data.data.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = `${cat.name} (${cat.products_count ?? 0})`;
                if (cat.id == currentCategory) opt.selected = true;
                select.appendChild(opt);
            });
            categoriesLoaded = true;
        }
    }

    async function fetchProducts(page = currentPage) {
        currentPage = page;
        const container = document.getElementById('products-container');
        const countEl = document.getElementById('product-count');
        const paginationContainer = document.getElementById('pagination-container');

        renderSkeletons(perPage);

        const search = document.getElementById('search-input').value.trim();
        const categoryId = document.getElementById('category-filter').value;
        const sortBy = document.getElementById('sort-filter').value;

        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('per_page', perPage);
        if (search) params.append('search', search);
        if (categoryId) params.append('category_id', categoryId);
        if (sortBy) params.append('sort_by', sortBy);

        const res = await apiFetch(`/api/products?${params.toString()}`);

        if (!res.ok) {
            container.innerHTML = `
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-rose-200 text-rose-600">
                    Failed to load products. ${res.data?.message || 'Server error.'}
                </div>
            `;
            countEl.textContent = 'Error loading products';
            paginationContainer.classList.add('hidden');
            return;
        }

        const products = res.data?.data || [];
        const meta = res.data?.meta || { total: products.length, current_page: 1, last_page: 1, per_page: perPage };

        totalPages = meta.last_page || 1;
        const totalProducts = meta.total || 0;
        const fromItem = totalProducts > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0;
        const toItem = Math.min(meta.current_page * meta.per_page, totalProducts);

        countEl.textContent = `Showing ${fromItem}-${toItem} of ${totalProducts} item(s)`;

        if (products.length === 0) {
            container.innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-2xl border border-slate-200 text-slate-500">
                    <p class="text-base font-bold text-navy-950">No products found</p>
                    <p class="text-xs text-slate-400 mt-1">Try adjusting your search keywords or category filters.</p>
                </div>
            `;
            paginationContainer.classList.add('hidden');
            return;
        }

        container.innerHTML = products.map(product => {
            const primaryImg = product.primary_image?.image 
                ? `/storage/${product.primary_image.image}` 
                : null;
            
            const inStock = product.stock > 0;
            const stockBadge = inStock
                ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">In Stock (${product.stock})</span>`
                : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Out of Stock</span>`;

            return `
                <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden flex flex-col justify-between hover:border-blue-300 hover:shadow-xl transition-all duration-300 group">
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
                                ${product.category?.name || 'Catalog Item'}
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
                            <button type="button" onclick="addToWishlist(${product.id})" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl border border-slate-200 transition-all" title="Add to Wishlist">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </button>
                        </div>
                        <button type="button" 
                                id="btn-add-${product.id}"
                                onclick="addToCart(${product.id})" 
                                ${!inStock ? 'disabled' : ''}
                                class="w-full py-2.5 px-3 text-xs font-bold rounded-xl text-white transition-all shadow-sm ${inStock ? 'bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 shadow-blue-700/20' : 'bg-slate-300 cursor-not-allowed'}">
                            ${inStock ? 'Add to Cart' : 'Out of Stock'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        renderPaginationUI(meta);
    }

    function renderPaginationUI(meta) {
        const paginationContainer = document.getElementById('pagination-container');
        const summaryEl = document.getElementById('pagination-summary');
        const buttonsEl = document.getElementById('pagination-buttons');

        if (meta.total <= meta.per_page && meta.current_page === 1) {
            paginationContainer.classList.add('hidden');
            return;
        }

        paginationContainer.classList.remove('hidden');

        const totalProducts = meta.total || 0;
        const fromItem = (meta.current_page - 1) * meta.per_page + 1;
        const toItem = Math.min(meta.current_page * meta.per_page, totalProducts);
        summaryEl.textContent = `Showing ${fromItem}-${toItem} of ${totalProducts} products`;

        let btnsHtml = '';

        // Previous button
        btnsHtml += `
            <button type="button" onclick="goToPage(${meta.current_page - 1})"
                    ${meta.current_page === 1 ? 'disabled' : ''}
                    class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 ${meta.current_page === 1 ? 'text-slate-300 cursor-not-allowed bg-slate-50' : 'text-navy-950 hover:bg-slate-100'}">
                &larr; Prev
            </button>
        `;

        // Page numbers
        for (let p = 1; p <= meta.last_page; p++) {
            if (p === 1 || p === meta.last_page || (p >= meta.current_page - 1 && p <= meta.current_page + 1)) {
                btnsHtml += `
                    <button type="button" onclick="goToPage(${p})"
                            class="px-3 py-1.5 text-xs rounded-xl font-bold border transition-all ${p === meta.current_page ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'text-slate-700 hover:bg-slate-100 border-slate-200'}">
                        ${p}
                    </button>
                `;
            } else if (p === meta.current_page - 2 || p === meta.current_page + 2) {
                btnsHtml += `<span class="px-1 text-slate-400 text-xs font-bold">...</span>`;
            }
        }

        // Next button
        btnsHtml += `
            <button type="button" onclick="goToPage(${meta.current_page + 1})"
                    ${meta.current_page === meta.last_page ? 'disabled' : ''}
                    class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 ${meta.current_page === meta.last_page ? 'text-slate-300 cursor-not-allowed bg-slate-50' : 'text-navy-950 hover:bg-slate-100'}">
                Next &rarr;
            </button>
        `;

        buttonsEl.innerHTML = btnsHtml;
    }

    function goToPage(page) {
        if (page < 1 || page > totalPages || page === currentPage) return;
        fetchProducts(page);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function changePerPage(val) {
        perPage = parseInt(val) || 12;
        currentPage = 1;
        fetchProducts(1);
    }

    function changeFilter() {
        currentPage = 1;
        fetchProducts(1);
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    const handleSearchDebounce = debounce(() => {
        currentPage = 1;
        fetchProducts(1);
    }, 350);

    async function addToCart(productId) {
        if (!getAuthToken()) {
            showAlert('product-alert', 'Please login to add products to your cart.', 'warning');
            setTimeout(() => window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname), 1200);
            return;
        }

        const btn = document.getElementById(`btn-add-${productId}`);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Adding...';
        }

        const res = await apiFetch('/api/cart/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });

        if (res.ok) {
            showAlert('product-alert', 'Product added to cart successfully!', 'success');
            if (btn) {
                btn.textContent = '✓ Added to Cart';
                btn.className = 'w-full py-2.5 px-3 text-xs font-bold rounded-xl text-white bg-emerald-600 transition-all';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = 'Add to Cart';
                    btn.className = 'w-full py-2.5 px-3 text-xs font-bold rounded-xl text-white bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 shadow-sm transition-all';
                }, 1500);
            }
            fetchNavbarCounts(true);
        } else {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Add to Cart';
            }
            showAlert('product-alert', res.data?.message || 'Failed to add to cart.', 'danger');
        }
    }

    async function addToWishlist(productId) {
        if (!getAuthToken()) {
            showAlert('product-alert', 'Please login to add products to your wishlist.', 'warning');
            setTimeout(() => window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname), 1200);
            return;
        }

        const res = await apiFetch('/api/wishlist/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId })
        });

        if (res.ok) {
            showAlert('product-alert', 'Product saved to wishlist!', 'success');
            fetchNavbarCounts(true);
        } else {
            showAlert('product-alert', res.data?.message || 'Failed to save to wishlist.', 'danger');
        }
    }

    function applyFilters(e) {
        e.preventDefault();
        currentPage = 1;
        fetchProducts(1);
    }

    function resetFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('category-filter').value = '';
        document.getElementById('sort-filter').value = 'latest';
        currentPage = 1;
        fetchProducts(1);
    }

    document.addEventListener('DOMContentLoaded', () => {
        Promise.all([
            loadCategories(),
            fetchProducts(1)
        ]);
    });
</script>
@endpush
