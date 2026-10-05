@extends('layouts.app')

@section('title', 'Search Products - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-8">
    <!-- Search Hero Header -->
    <div class="relative bg-gradient-to-br from-navy-975 via-navy-900 to-navy-850 rounded-3xl border border-navy-800 p-8 sm:p-12 text-white shadow-2xl overflow-hidden">
        <!-- Background Glow -->
        <div class="absolute -top-20 -right-20 w-80 h-80 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-3xl relative z-10 space-y-4">
            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-900/60 text-blue-300 border border-blue-700/50 shadow-inner">
                🔍 Storewide Live Search
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Find Products Instantly</h1>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                Search our comprehensive catalog by product name, description, tags, or category.
            </p>

            <!-- Search Form -->
            <form onsubmit="handleSearchSubmit(event)" class="pt-2">
                <div class="relative flex items-center shadow-lg rounded-2xl overflow-hidden bg-navy-950/90 border border-blue-500/30 p-1.5 focus-within:ring-2 focus-within:ring-blue-500/40 focus-within:border-blue-400 transition-all">
                    <div class="pl-3.5 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="main-search-input"
                           value="{{ $query ?? '' }}"
                           placeholder="Type keywords, e.g. 'headphones', 'jacket', 'laptop'..."
                           class="w-full px-4 py-3 bg-transparent text-white text-sm sm:text-base outline-none border-0 focus:ring-0 placeholder-slate-400"
                           oninput="onSearchTyping()">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-blue-700/20 shrink-0">
                        Search
                    </button>
                </div>
            </form>

            <!-- Quick Popular Searches -->
            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                <span class="text-slate-400 font-bold">Popular Queries:</span>
                <button type="button" onclick="quickSearch('laptop')" class="px-3 py-1 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white font-semibold transition-colors">Laptop</button>
                <button type="button" onclick="quickSearch('shoes')" class="px-3 py-1 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white font-semibold transition-colors">Shoes</button>
                <button type="button" onclick="quickSearch('watch')" class="px-3 py-1 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white font-semibold transition-colors">Watch</button>
                <button type="button" onclick="quickSearch('wireless')" class="px-3 py-1 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white font-semibold transition-colors">Wireless</button>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="search-alert"></div>

    <!-- Filters & Sort Controls -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3">
            <span id="results-summary" class="text-xs sm:text-sm font-bold text-navy-950">Loading catalog...</span>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <select id="category-filter" onchange="runSearch()" class="text-xs sm:text-sm border border-slate-200 rounded-xl px-3.5 py-2 bg-slate-50 text-navy-950 font-semibold outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div>
                <select id="sort-filter" onchange="runSearch()" class="text-xs sm:text-sm border border-slate-200 rounded-xl px-3.5 py-2 bg-slate-50 text-navy-950 font-semibold outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="latest">Latest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A to Z</option>
                </select>
            </div>
            <button type="button" onclick="resetFilters()" class="text-xs text-blue-600 font-bold hover:underline">
                Clear Filters
            </button>
        </div>
    </div>

    <!-- Search Results Grid -->
    <div id="search-results-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Products populated via JavaScript -->
    </div>

    <!-- Empty State Container -->
    <div id="no-results-state" class="hidden text-center py-16 bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
        <div class="w-16 h-16 mx-auto mb-4 text-blue-600 bg-blue-50 rounded-2xl flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-extrabold text-navy-950">No matching products found</h3>
        <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto">
            Try checking your spelling, removing search filters, or exploring our full products catalog.
        </p>
        <div class="mt-6">
            <a href="{{ url('/products') }}" class="inline-flex items-center px-6 py-3 rounded-xl shadow-md shadow-blue-700/20 text-xs font-bold text-white bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 transition-all">
                Browse Full Catalog &rarr;
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let searchDebounceTimer = null;

    document.addEventListener('DOMContentLoaded', async () => {
        await loadCategories();
        await runSearch();
    });

    async function loadCategories() {
        const catSelect = document.getElementById('category-filter');
        try {
            const res = await apiFetch('/api/categories');
            if (res.ok && Array.isArray(res.data?.data)) {
                res.data.data.forEach(c => {
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

        const url = new URL(window.location);
        if (query) url.searchParams.set('q', query);
        else url.searchParams.delete('q');
        window.history.replaceState({}, '', url);

        const params = new URLSearchParams();
        if (query) params.append('search', query);
        if (categoryId) params.append('category_id', categoryId);
        if (sort) params.append('sort_by', sort);

        summary.textContent = 'Searching catalog...';
        grid.innerHTML = `
            <div class="col-span-full py-12 flex justify-center items-center text-slate-400">
                <span class="text-xs font-bold animate-pulse">Fetching matching items...</span>
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
                const imgSrc = rawImg ? (rawImg.startsWith('http') ? rawImg : `/storage/${rawImg.replace(/^\/+/, '')}`) : null;

                const inStock = (p.stock ?? 0) > 0;
                const card = document.createElement('div');
                card.className = 'bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs hover:border-blue-300 hover:shadow-xl transition-all duration-300 flex flex-col justify-between group';
                card.innerHTML = `
                    <div>
                        <div class="relative h-48 bg-slate-50 flex items-center justify-center overflow-hidden">
                            ${imgSrc 
                                ? `<img src="${imgSrc}" alt="${escapeHtml(p.name)}" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300">`
                                : `<span class="text-xs text-slate-400">No Image</span>`}
                            ${!inStock ? '<span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 text-[10px] font-bold text-rose-800 bg-rose-100 border border-rose-200 rounded-full">Out of Stock</span>' : ''}
                        </div>
                        <div class="p-5">
                            <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider mb-1">
                                ${p.category?.name || 'General Item'}
                            </div>
                            <h3 class="font-bold text-sm text-navy-950 line-clamp-1 group-hover:text-blue-600 transition-colors">
                                <a href="/products/${p.id}">${escapeHtml(p.name)}</a>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">
                                ${escapeHtml(p.description || '')}
                            </p>
                        </div>
                    </div>
                    <div class="p-5 pt-0">
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-base font-extrabold text-navy-950">$${parseFloat(p.price).toFixed(2)}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="/products/${p.id}" class="p-2 text-slate-400 hover:text-blue-600 border border-slate-200 rounded-xl hover:border-blue-300 transition-colors" title="View details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <button type="button" id="btn-search-add-${p.id}" onclick="quickAddToCart(${p.id})" ${!inStock ? 'disabled' : ''} class="px-3.5 py-1.5 text-xs font-bold text-white bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 disabled:bg-slate-200 rounded-xl transition-all shadow-xs">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });

        } catch (e) {
            console.error('Search query error', e);
            summary.textContent = 'Error connecting to search endpoint.';
        }
    }

    async function quickAddToCart(productId) {
        if (!getAuthToken()) {
            showAlert('search-alert', 'Please log in to add items to your shopping cart.', 'warning');
            setTimeout(() => { window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search); }, 1000);
            return;
        }

        const btn = document.getElementById(`btn-search-add-${productId}`);
        if (btn) {
            btn.disabled = true;
            btn.textContent = '...';
        }

        const res = await apiFetch('/api/cart/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });

        if (res.ok) {
            showAlert('search-alert', 'Product added to cart!', 'success');
            if (btn) {
                btn.textContent = '✓ Added';
                btn.className = 'px-3.5 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded-xl transition-colors';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = 'Add';
                    btn.className = 'px-3.5 py-1.5 text-xs font-bold text-white bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 rounded-xl transition-all shadow-xs';
                }, 1500);
            }
            if (typeof fetchNavbarCounts === 'function') fetchNavbarCounts(true);
        } else {
            showAlert('search-alert', res.data?.message || 'Failed to add item to cart.', 'danger');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Add';
            }
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>
@endpush
