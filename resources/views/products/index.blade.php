@extends('layouts.app')

@section('title', 'Products - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Products Catalog</h1>
            <p class="text-sm text-gray-500">Fast, optimized product listing with filtering, pagination, and stock checks.</p>
        </div>
        <div id="product-count" class="text-sm font-medium text-gray-600">
            Loading products...
        </div>
    </div>

    <!-- Alert Container -->
    <div id="product-alert"></div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
        <form id="filter-form" onsubmit="applyFilters(event)" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <!-- Search Input -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Search</label>
                <div class="relative">
                    <input type="text" id="search-input"
                           oninput="handleSearchDebounce()"
                           class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                           placeholder="Search by name or description...">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Category</label>
                <select id="category-filter" onchange="changeFilter()"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    <option value="">All Categories</option>
                </select>
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sort By</label>
                <select id="sort-filter" onchange="changeFilter()"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    <option value="latest">Newest First</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A - Z</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-1.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors">
                    Filter
                </button>
                <button type="button" onclick="resetFilters()" class="py-1.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors">
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
    <div id="pagination-container" class="hidden bg-white p-4 rounded-lg border border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3 text-sm text-gray-600">
            <span id="pagination-summary">Showing 1-12 of 0 products</span>
            <span class="text-gray-300">|</span>
            <div class="flex items-center space-x-1.5">
                <label for="per-page-select" class="text-xs text-gray-500">Per page:</label>
                <select id="per-page-select" onchange="changePerPage(this.value)" class="text-xs border border-gray-300 rounded px-2 py-1 bg-white outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="12">12</option>
                    <option value="24">24</option>
                    <option value="48">48</option>
                </select>
            </div>
        </div>

        <div class="flex items-center space-x-1" id="pagination-buttons">
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

    // Render skeleton placeholders to avoid layout shift
    function renderSkeletons(count = 8) {
        const container = document.getElementById('products-container');
        container.innerHTML = Array.from({ length: count }).map(() => `
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden flex flex-col justify-between animate-pulse">
                <div>
                    <div class="h-48 w-full bg-gray-200"></div>
                    <div class="p-4 space-y-2.5">
                        <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                        <div class="h-5 bg-gray-200 rounded w-1/4 mt-2"></div>
                    </div>
                </div>
                <div class="p-4 pt-0 border-t border-gray-100 mt-2 space-y-2">
                    <div class="flex space-x-2 pt-2">
                        <div class="h-7 bg-gray-200 rounded flex-1"></div>
                        <div class="h-7 w-8 bg-gray-200 rounded"></div>
                    </div>
                    <div class="h-8 bg-gray-200 rounded w-full"></div>
                </div>
            </div>
        `).join('');
    }

    async function loadCategories() {
        if (categoriesLoaded) return;
        const select = document.getElementById('category-filter');
        const res = await apiFetch('/api/categories');
        if (res.ok && Array.isArray(res.data?.data)) {
            // Keep first option
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
                <div class="col-span-full p-8 text-center bg-white rounded-lg border border-red-200 text-red-600">
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

        countEl.textContent = `Showing ${fromItem}-${toItem} of ${totalProducts} product(s)`;

        if (products.length === 0) {
            container.innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-lg border border-gray-200 text-gray-500">
                    <p class="text-base font-medium text-gray-700">No products found</p>
                    <p class="text-sm mt-1">Try adjusting your search keywords or category filters.</p>
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
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">In Stock (${product.stock})</span>`
                : `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Out of Stock</span>`;

            return `
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden flex flex-col justify-between hover:border-gray-300 hover:shadow-sm transition-all">
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
                            <div class="text-xs text-indigo-600 font-semibold uppercase tracking-wider mb-1">
                                ${product.category?.name || 'Uncategorized'}
                            </div>
                            <h3 class="text-base font-bold text-gray-900 line-clamp-1" title="${product.name}">
                                ${product.name}
                            </h3>
                            <div class="mt-2 text-lg font-bold text-gray-900">
                                $${parseFloat(product.price).toFixed(2)}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 pt-0 border-t border-gray-100 mt-2 flex flex-col gap-2">
                        <div class="flex items-center space-x-2 pt-2">
                            <a href="/products/${product.id}" class="flex-1 text-center py-1.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold rounded transition-colors">
                                Details
                            </a>
                            <button type="button" onclick="addToWishlist(${product.id})" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded border border-gray-200 transition-colors" title="Add to Wishlist">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </button>
                        </div>
                        <button type="button" 
                                id="btn-add-${product.id}"
                                onclick="addToCart(${product.id})" 
                                ${!inStock ? 'disabled' : ''}
                                class="w-full py-2 px-3 text-xs font-bold rounded text-white transition-colors ${inStock ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 cursor-not-allowed'}">
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
                    class="px-2.5 py-1 text-xs rounded border border-gray-300 ${meta.current_page === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'}">
                &larr; Prev
            </button>
        `;

        // Page numbers
        for (let p = 1; p <= meta.last_page; p++) {
            if (p === 1 || p === meta.last_page || (p >= meta.current_page - 1 && p <= meta.current_page + 1)) {
                btnsHtml += `
                    <button type="button" onclick="goToPage(${p})"
                            class="px-2.5 py-1 text-xs rounded font-medium border ${p === meta.current_page ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-700 hover:bg-gray-100 border-gray-300'}">
                        ${p}
                    </button>
                `;
            } else if (p === meta.current_page - 2 || p === meta.current_page + 2) {
                btnsHtml += `<span class="px-1 text-gray-400 text-xs">...</span>`;
            }
        }

        // Next button
        btnsHtml += `
            <button type="button" onclick="goToPage(${meta.current_page + 1})"
                    ${meta.current_page === meta.last_page ? 'disabled' : ''}
                    class="px-2.5 py-1 text-xs rounded border border-gray-300 ${meta.current_page === meta.last_page ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'}">
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

        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Add to Cart';
        }

        if (res.ok) {
            showAlert('product-alert', 'Product added to cart successfully!', 'success');
            fetchNavbarCounts(true);
        } else {
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

    // Parallel fetch: Load categories and products concurrently with zero waterfall
    document.addEventListener('DOMContentLoaded', () => {
        Promise.all([
            loadCategories(),
            fetchProducts(1)
        ]);
    });
</script>
@endpush
