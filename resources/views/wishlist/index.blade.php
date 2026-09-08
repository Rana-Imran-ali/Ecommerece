@extends('layouts.app')

@section('title', 'My Wishlist - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Saved Wishlist</h1>
            <p class="text-sm text-gray-500">View bookmarked products and move them to your active cart.</p>
        </div>
        <button type="button" id="clear-wishlist-btn" onclick="clearWholeWishlist()"
                class="hidden text-sm font-medium text-red-600 hover:text-red-800 transition-colors">
            Clear Wishlist
        </button>
    </div>

    <!-- Alert Container -->
    <div id="wishlist-alert"></div>

    <!-- Wishlist Container Grid -->
    <div id="wishlist-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div class="col-span-full py-16 text-center text-gray-500 bg-white rounded-lg border border-gray-200">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
            <div>Loading wishlist items...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadWishlist() {
        if (!getAuthToken()) {
            document.getElementById('wishlist-container').innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-lg border border-gray-200 space-y-4">
                    <p class="text-lg font-semibold text-gray-800">Please sign in to view your wishlist</p>
                    <a href="/login?redirect=/wishlist" class="inline-block px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                        Sign In Now
                    </a>
                </div>
            `;
            return;
        }

        const res = await apiFetch('/api/wishlist');
        const container = document.getElementById('wishlist-container');
        const clearBtn = document.getElementById('clear-wishlist-btn');

        if (!res.ok) {
            container.innerHTML = `
                <div class="col-span-full p-8 text-center bg-white rounded-lg border border-red-200 text-red-600">
                    Failed to load wishlist: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        const items = res.data?.data?.items || [];

        if (items.length === 0) {
            clearBtn.classList.add('hidden');
            container.innerHTML = `
                <div class="col-span-full p-16 text-center bg-white rounded-lg border border-gray-200 space-y-4">
                    <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-800">Your wishlist is empty</h3>
                    <p class="text-sm text-gray-500">Save your favorite items here while exploring the store.</p>
                    <a href="/products" class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        Discover Products
                    </a>
                </div>
            `;
            return;
        }

        clearBtn.classList.remove('hidden');

        container.innerHTML = items.map(item => {
            const p = item.product || {};
            const img = p.primary_image?.image 
                ? `/storage/${p.primary_image.image}` 
                : (p.images?.[0]?.image ? `/storage/${p.images[0].image}` : null);
            const inStock = (p.stock ?? 0) > 0;

            return `
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden flex flex-col justify-between hover:border-gray-300 transition-colors">
                    <div>
                        <!-- Image Container -->
                        <div class="h-44 w-full bg-gray-100 flex items-center justify-center relative overflow-hidden">
                            ${img 
                                ? `<img src="${img}" alt="${p.name}" class="h-full w-full object-cover">`
                                : `<span class="text-xs text-gray-400">No Image</span>`}
                            <button type="button" onclick="removeWishlistItem(${item.id})"
                                    class="absolute top-2 right-2 p-1.5 bg-white/90 hover:bg-white text-gray-400 hover:text-red-600 rounded-full shadow-sm transition-colors"
                                    title="Remove from wishlist">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-4">
                            <div class="text-xs text-indigo-600 font-semibold uppercase tracking-wider mb-1">
                                ${p.category?.name || 'General'}
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 line-clamp-1" title="${p.name}">
                                ${p.name}
                            </h3>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-base font-bold text-gray-900">$${parseFloat(p.price || 0).toFixed(2)}</span>
                                <span class="text-xs ${inStock ? 'text-green-600' : 'text-red-600'} font-medium">
                                    ${inStock ? `In Stock (${p.stock})` : 'Out of Stock'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="p-4 pt-0">
                        <button type="button" onclick="moveToCart(${item.id}, ${p.id})"
                                ${!inStock ? 'disabled' : ''}
                                class="w-full py-2 px-3 text-xs font-bold rounded text-white transition-colors ${inStock ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 cursor-not-allowed'}">
                            ${inStock ? 'Move to Cart' : 'Unavailable'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function removeWishlistItem(itemId) {
        const res = await apiFetch(`/api/wishlist/items/${itemId}`, {
            method: 'DELETE'
        });

        if (res.ok) {
            showAlert('wishlist-alert', 'Item removed from wishlist.', 'success');
            loadWishlist();
            fetchNavbarCounts();
        } else {
            showAlert('wishlist-alert', res.data?.message || 'Failed to remove item.', 'danger');
        }
    }

    async function moveToCart(wishlistItemId, productId) {
        // 1. Add to cart
        const addRes = await apiFetch('/api/cart/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });

        if (addRes.ok) {
            // 2. Remove from wishlist
            await apiFetch(`/api/wishlist/items/${wishlistItemId}`, { method: 'DELETE' });
            showAlert('wishlist-alert', 'Moved to cart successfully!', 'success');
            loadWishlist();
            fetchNavbarCounts();
        } else {
            showAlert('wishlist-alert', addRes.data?.message || 'Failed to move to cart.', 'danger');
        }
    }

    async function clearWholeWishlist() {
        if (!confirm('Clear all items from your wishlist?')) return;

        const res = await apiFetch('/api/wishlist', {
            method: 'DELETE'
        });

        if (res.ok) {
            showAlert('wishlist-alert', 'Wishlist cleared.', 'success');
            loadWishlist();
            fetchNavbarCounts();
        } else {
            showAlert('wishlist-alert', res.data?.message || 'Failed to clear wishlist.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', loadWishlist);
</script>
@endpush
