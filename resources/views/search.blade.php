@extends('layouts.app')

@section('title', 'Search Products - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6">
    <!-- Search Hero Header -->
    <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-purple-700 rounded-2xl p-6 sm:p-10 text-white shadow-lg">
        <div class="max-w-3xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white mb-3 backdrop-blur-sm">
                🔍 Storewide Search
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Find Products Instantly</h1>
            <p class="mt-2 text-indigo-100 text-sm sm:text-base leading-relaxed">
                Search our comprehensive catalog by product name, description, tags, or category.
            </p>

            <!-- Search Form -->
            <form onsubmit="handleSearchSubmit(event)" class="mt-6">
                <div class="relative flex items-center shadow-md rounded-xl overflow-hidden bg-white">
                    <div class="pl-4 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="main-search-input"
                           value="{{ $query ?? '' }}"
                           placeholder="Type keywords, e.g. 'headphones', 'jacket', 'laptop'..."
                           class="w-full px-4 py-3.5 text-gray-800 text-sm sm:text-base outline-none border-0 focus:ring-0"
                           oninput="onSearchTyping()">
                    <button type="submit" class="px-6 py-3.5 bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition-colors shrink-0">
                        Search
                    </button>
                </div>
            </form>

            <!-- Quick Popular Searches -->
            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                <span class="text-indigo-200">Popular:</span>
                <button type="button" onclick="quickSearch('laptop')" class="px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/25 transition-colors">Laptop</button>
                <button type="button" onclick="quickSearch('shoes')" class="px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/25 transition-colors">Shoes</button>
                <button type="button" onclick="quickSearch('watch')" class="px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/25 transition-colors">Watch</button>
                <button type="button" onclick="quickSearch('wireless')" class="px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/25 transition-colors">Wireless</button>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="search-alert"></div>

    <!-- Filters & Sort Controls -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3">
            <span id="results-summary" class="text-sm font-semibold text-gray-700">Loading catalog...</span>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <select id="category-filter" onchange="runSearch()" class="text-xs sm:text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white text-gray-700 outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div>
                <select id="sort-filter" onchange="runSearch()" class="text-xs sm:text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white text-gray-700 outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="latest">Latest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A to Z</option>
                </select>
            </div>
            <button type="button" onclick="resetFilters()" class="text-xs text-gray-500 hover:text-indigo-600 underline">
                Clear Filters
            </button>
        </div>
    </div>

    <!-- Search Results Grid -->
    <div id="search-results-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Products populated via JavaScript -->
    </div>

    <!-- Empty State Container -->
    <div id="no-results-state" class="hidden text-center py-16 bg-white rounded-xl border border-gray-200 p-8">
        <div class="w-16 h-16 mx-auto mb-4 text-indigo-400 bg-indigo-50 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900">No matching products found</h3>
        <p class="mt-1 text-sm text-gray-500 max-w-md mx-auto">
            Try checking your spelling, removing search filters, or exploring our full products catalog.
        </p>
        <div class="mt-6">
            <a href="{{ url('/products') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Browse Full Catalog
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let searchDebounceTimer = null;

    document.addEventListener('DOMContentLoaded', async () => {
        await loadCategories();
        const initialQuery = document.getElementById('main-search-input').value.trim();
        await runSearch();
    });

    async function loadCategories() {
        const catSelect = document.getElementById('category-filter');
        try {
            const res = await apiFetch('/api/categories');
            if (res.ok && Array.isArray(res.data)) {
                res.data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    catSelect.appendChild(opt);
                });
            }
        } catch (e) {
            console.error('Failed to load categories', e);
        }
    }

    function onSearchTyping() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            runSearch();
        }, 350);
    }

    function handleSearchSubmit(e) {
        e.preventDefault();
        clearTimeout(searchDebounceTimer);
        runSearch();
    }

    function quickSearch(term) {
        document.getElementById('main-search-input').value = term;
        runSearch();
    }

    function resetFilters() {
        document.getElementById('main-search-input').value = '';
        document.getElementById('category-filter').value = '';
        document.getElementById('sort-filter').value = 'latest';
        runSearch();
    }

    async function runSearch() {
        const query = document.getElementById('main-search-input').value.trim();
        const categoryId = document.getElementById('category-filter').value;
        const sort = document.getElementById('sort-filter').value;
        const grid = document.getElementById('search-results-grid');
        const emptyState = document.getElementById('no-results-state');
        const summary = document.getElementById('results-summary');

        // Update URL query parameter cleanly without reloading
        const url = new URL(window.location);
        if (query) url.searchParams.set('q', query);
        else url.searchParams.delete('q');
        window.history.replaceState({}, '', url);

        // Build API URL
        const params = new URLSearchParams();
        if (query) params.append('search', query);
        if (categoryId) params.append('category_id', categoryId);
        if (sort) params.append('sort_by', sort);

        summary.textContent = 'Searching catalog...';
        grid.innerHTML = `
            <div class="col-span-full py-12 flex justify-center items-center space-x-3 text-gray-500">
                <div class="animate-spin w-5 h-5 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
                <span class="text-sm">Fetching products...</span>
            </div>
        `;
        emptyState.classList.add('hidden');

        try {
            const res = await apiFetch(`/api/products?${params.toString()}`);
            if (!res.ok) {
                summary.textContent = 'Error loading results';
                return;
            }

            const products = Array.isArray(res.data) ? res.data : (res.data?.data || []);
            grid.innerHTML = '';

            if (products.length === 0) {
                summary.textContent = query ? `0 results for "${query}"` : '0 products found';
                emptyState.classList.remove('hidden');
                return;
            }

            summary.textContent = query 
                ? `Found ${products.length} result${products.length > 1 ? 's' : ''} for "${query}"`
                : `Showing ${products.length} products`;

            products.forEach(p => {
                const rawImg = p.primary_image?.image || (p.images && p.images.length > 0 ? (p.images.find(i => i.is_primary)?.image || p.images[0].image) : null);
                const imgSrc = rawImg ? (rawImg.startsWith('http') ? rawImg : `/storage/${rawImg.replace(/^\/+/, '')}`) : 'https://placehold.co/400x300/e0e7ff/4f46e5?text=No+Image';

                const inStock = p.stock > 0;
                const card = document.createElement('div');
                card.className = 'bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col group';
                card.innerHTML = `
                    <div class="relative h-48 bg-gray-50 overflow-hidden">
                        <img src="${imgSrc}" alt="${escapeHtml(p.name)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        ${!inStock ? '<span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-md">Out of Stock</span>' : ''}
                    </div>
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                                ${p.category?.name || 'General'}
                            </div>
                            <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-indigo-600 transition-colors">
                                ${escapeHtml(p.name)}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                ${escapeHtml(p.description || '')}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <div>
                                <span class="text-lg font-black text-gray-900">$${parseFloat(p.price).toFixed(2)}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="/products/${p.id}" class="p-2 text-gray-500 hover:text-indigo-600 border border-gray-200 rounded-lg hover:border-indigo-300 transition-colors" title="View details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <button type="button" onclick="quickAddToCart(${p.id})" ${p.stock_quantity <= 0 ? 'disabled' : ''} class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-lg transition-colors">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });

        } catch (err) {
            console.error('Search error', err);
            summary.textContent = 'Error connecting to search service';
        }
    }

    async function quickAddToCart(productId) {
        try {
            const res = await apiFetch('/api/cart/items', {
                method: 'POST',
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            });
            if (res.ok) {
                showToast('Product added to cart!', 'success');
                if (typeof fetchNavbarCounts === 'function') fetchNavbarCounts(true);
            } else {
                showToast(res.data?.message || 'Could not add to cart', 'error');
            }
        } catch (e) {
            showToast('Please login to add items to cart.', 'error');
        }
    }

    function showToast(msg, type = 'success') {
        const alertBox = document.getElementById('search-alert');
        const bg = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
        alertBox.innerHTML = `
            <div class="p-3 rounded-lg border text-sm font-medium ${bg} flex justify-between items-center transition-all">
                <span>${msg}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-xs opacity-60 hover:opacity-100">✕</button>
            </div>
        `;
        setTimeout(() => { if (alertBox.firstChild) alertBox.firstChild.remove(); }, 3500);
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>
@endpush
@endsection
