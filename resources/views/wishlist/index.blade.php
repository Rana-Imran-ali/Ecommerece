@extends('layouts.app')

@section('title', 'My Wishlist - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Saved For Later</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy-950 tracking-tight mt-0.5">My Wishlist</h1>
            <p class="text-sm text-slate-500 mt-1">View bookmarked products and move them to your active shopping bag.</p>
        </div>
        <button type="button" id="clear-wishlist-btn" onclick="clearWholeWishlist()"
                class="hidden text-xs font-bold text-rose-600 hover:text-rose-800 transition-colors px-3 py-1.5 rounded-xl border border-rose-200 hover:bg-rose-50 self-start sm:self-auto">
            Clear Wishlist
        </button>
    </div>

    <!-- Alert Container -->
    <div id="wishlist-alert"></div>

    <!-- Wishlist Container Grid -->
    <div id="wishlist-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200 animate-pulse">
            <div class="text-sm font-semibold">Loading saved wishlist items...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadWishlist() {
        if (!isUserAuthenticated()) {
            document.getElementById('wishlist-container').innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                    <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </div>
                    <p class="text-lg font-bold text-navy-950">Please sign in to view your wishlist</p>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Sign in to save items across sessions and get notified about restocks.</p>
                    <a href="/login?redirect=/wishlist" class="inline-block px-6 py-3 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all">
                        Sign In Now &rarr;
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
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-rose-200 text-rose-600">
                    Failed to load wishlist: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        const items = res.data?.data?.items || [];

        if (items.length === 0) {
            clearBtn.classList.add('hidden');
            container.innerHTML = `
                <div class="col-span-full p-16 text-center bg-white rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-navy-950">Your wishlist is empty</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Save your favorite items here while exploring our product collections.</p>
                    <a href="/products" class="inline-block px-6 py-3 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all">
                        Discover Products &rarr;
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
                <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden flex flex-col justify-between hover:border-blue-300 hover:shadow-xl transition-all duration-300 group shadow-sm">
                    <div>
                        <!-- Image Container -->
                        <div class="h-48 w-full bg-slate-50 flex items-center justify-center relative overflow-hidden">
                            ${img 
                                ? `<img src="${img}" alt="${p.name}" class="h-full w-full object-contain p-2 group-hover:scale-105 transition-transform duration-300">`
                                : `<span class="text-xs text-slate-400">No Image</span>`}
                            <button type="button" onclick="removeWishlistItem(${item.id})"
                                    class="absolute top-2.5 right-2.5 p-2 bg-white/90 hover:bg-white text-slate-400 hover:text-rose-600 rounded-xl shadow-xs transition-colors"
                                    title="Remove from wishlist">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-5">
                            <div class="text-[11px] text-blue-600 font-bold uppercase tracking-wider mb-1">
                                ${p.category?.name || 'General Item'}
                            </div>
                            <h3 class="text-sm font-bold text-navy-950 line-clamp-1 group-hover:text-blue-600 transition-colors" title="${p.name}">
                                <a href="/products/${p.id}">${p.name}</a>
                            </h3>
                            <div class="mt-2.5 flex items-center justify-between">
                                <span class="text-base font-extrabold text-navy-950">$${parseFloat(p.price || 0).toFixed(2)}</span>
                                <span class="text-[11px] ${inStock ? 'text-emerald-800 bg-emerald-100 border border-emerald-200' : 'text-rose-800 bg-rose-100 border border-rose-200'} px-2.5 py-0.5 rounded-full font-bold">
                                    ${inStock ? `In Stock (${p.stock})` : 'Out of Stock'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="p-5 pt-0">
                        <button type="button" id="btn-move-${item.id}" onclick="moveToCart(${item.id}, ${p.id})"
                                ${!inStock ? 'disabled' : ''}
                                class="w-full py-2.5 px-3 text-xs font-bold rounded-xl text-white transition-all ${inStock ? 'bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 shadow-md shadow-blue-700/20' : 'bg-slate-300 cursor-not-allowed'}">
                            ${inStock ? 'Move to Cart &rarr;' : 'Unavailable'}
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
            showAlert('wishlist-alert', 'Item removed from wishlist.', 'info');
            loadWishlist();
            if (typeof fetchNavbarCounts === 'function') fetchNavbarCounts(true);
        } else {
            showAlert('wishlist-alert', res.data?.message || 'Failed to remove item.', 'danger');
        }
    }

    async function moveToCart(wishlistItemId, productId) {
        const btn = document.getElementById(`btn-move-${wishlistItemId}`);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Moving...';
        }

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
            if (typeof fetchNavbarCounts === 'function') fetchNavbarCounts(true);
        } else {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Move to Cart &rarr;';
            }
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
