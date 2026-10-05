@extends('layouts.app')

@section('title', 'Shopping Cart - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Review &amp; Checkout</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy-950 tracking-tight mt-0.5">Shopping Cart</h1>
            <p class="text-sm text-slate-500 mt-1">Review your selected items, adjust quantities, and proceed to checkout.</p>
        </div>
        <button type="button" id="clear-cart-btn" onclick="clearWholeCart()" 
                class="hidden text-xs font-bold text-rose-600 hover:text-rose-800 transition-colors px-3 py-1.5 rounded-xl border border-rose-200 hover:bg-rose-50 self-start sm:self-auto">
            Clear Entire Cart
        </button>
    </div>

    <!-- Alert Container -->
    <div id="cart-alert"></div>

    <!-- Main Content Grid -->
    <div id="cart-wrapper" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Loading State Placeholder -->
        <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200 animate-pulse">
            <div class="text-sm font-semibold">Loading your shopping cart...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadCart() {
        if (!getAuthToken()) {
            document.getElementById('cart-wrapper').innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <p class="text-lg font-bold text-navy-950">You must be signed in to view your cart</p>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Sign in to sync your items across devices and access saved shipping addresses.</p>
                    <a href="/login?redirect=/cart" class="inline-block px-6 py-3 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all">
                        Sign In Now &rarr;
                    </a>
                </div>
            `;
            return;
        }

        const res = await apiFetch('/api/cart');
        const wrapper = document.getElementById('cart-wrapper');
        const clearBtn = document.getElementById('clear-cart-btn');

        if (!res.ok) {
            wrapper.innerHTML = `
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-rose-200 text-rose-600">
                    Failed to fetch cart: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        const cart = res.data?.data;
        const items = cart?.items || [];

        if (items.length === 0) {
            clearBtn.classList.add('hidden');
            wrapper.innerHTML = `
                <div class="col-span-full p-16 text-center bg-white rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-navy-950">Your cart is empty</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Explore handpicked products from our catalog and find great deals.</p>
                    <a href="/products" class="inline-block px-6 py-3 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all">
                        Browse Catalog &rarr;
                    </a>
                </div>
            `;
            return;
        }

        clearBtn.classList.remove('hidden');

        // Render Table of Items + Summary Card
        wrapper.innerHTML = `
            <!-- Items Column -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-3xl border border-slate-200/90 overflow-hidden divide-y divide-slate-100 shadow-sm">
                    ${items.map(item => {
                        const p = item.product || {};
                        const v = item.variant || null;
                        const img = p.primary_image?.image 
                            ? `/storage/${p.primary_image.image}` 
                            : (p.images?.[0]?.image ? `/storage/${p.images[0].image}` : null);
                        const unitPrice = v ? parseFloat(v.effective_price || v.price) : parseFloat(p.price || 0);
                        const lineTotal = (unitPrice * item.quantity).toFixed(2);
                        const maxStock = v ? (v.stock ?? 0) : (p.stock ?? 0);

                        return `
                            <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="w-20 h-20 bg-slate-50 rounded-2xl border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center p-1">
                                        ${img ? `<img src="${img}" class="w-full h-full object-contain">` : `<span class="text-xs text-slate-400">No Image</span>`}
                                    </div>
                                    <div>
                                        <a href="/products/${p.id}" class="text-sm font-bold text-navy-950 hover:text-blue-600 transition-colors line-clamp-1">
                                            ${p.name || 'Product'}
                                        </a>
                                        ${v ? `<div class="text-[11px] text-blue-700 font-bold mt-0.5">Option: ${v.title}</div>` : ''}
                                        <div class="text-xs text-slate-500 mt-0.5 font-semibold">
                                            $${unitPrice.toFixed(2)} each &bull; Stock: ${maxStock}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end space-x-6">
                                    <!-- Stepper Quantity Controller -->
                                    <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                        <button type="button" onclick="updateCartItemQty(${item.id}, ${item.quantity - 1})"
                                                class="px-3 py-1.5 text-slate-600 hover:bg-slate-200 font-bold transition-colors"
                                                ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                        <span class="px-3 py-1.5 text-xs font-bold text-navy-950">${item.quantity}</span>
                                        <button type="button" onclick="updateCartItemQty(${item.id}, ${item.quantity + 1})"
                                                class="px-3 py-1.5 text-slate-600 hover:bg-slate-200 font-bold transition-colors"
                                                ${item.quantity >= maxStock ? 'disabled' : ''}>+</button>
                                    </div>

                                    <!-- Line Price -->
                                    <div class="text-base font-extrabold text-navy-950 w-24 text-right">
                                        $${lineTotal}
                                    </div>

                                    <!-- Remove Button -->
                                    <button type="button" onclick="removeCartItem(${item.id})" class="text-slate-400 hover:text-rose-600 p-2 rounded-xl hover:bg-rose-50 transition-colors" title="Remove item">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-7 space-y-4 shadow-sm sticky top-24">
                    <h2 class="text-base font-extrabold text-navy-950 border-b border-slate-100 pb-3">Order Summary</h2>

                    <div class="flex justify-between text-xs font-semibold text-slate-600">
                        <span>Total Items:</span>
                        <span class="font-bold text-navy-950">${cart.total_items}</span>
                    </div>

                    <div class="flex justify-between text-xs font-semibold text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-bold text-navy-950">$${parseFloat(cart.subtotal).toFixed(2)}</span>
                    </div>

                    <div class="flex justify-between text-xs font-semibold text-slate-600">
                        <span>Estimated Shipping:</span>
                        <span class="text-emerald-600 font-bold">Complimentary</span>
                    </div>

                    <div class="border-t border-slate-100 pt-3 flex justify-between text-lg font-extrabold text-navy-950">
                        <span>Estimated Total:</span>
                        <span class="text-blue-700">$${parseFloat(cart.subtotal).toFixed(2)}</span>
                    </div>

                    <button type="button" onclick="proceedToCheckout()" 
                            class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-blue-700/20 mt-4 cursor-pointer">
                        Proceed to Checkout &rarr;
                    </button>

                    <a href="/shop" class="block text-center text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors pt-1">
                        &larr; Continue Shopping
                    </a>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-center space-x-2 text-[11px] font-semibold text-slate-500">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Encrypted 256-bit Stripe Checkout</span>
                    </div>
                </div>
            </div>
        `;
    }

    async function updateCartItemQty(itemId, newQty) {
        if (newQty < 1) return;

        const res = await apiFetch(`/api/cart/items/${itemId}`, {
            method: 'PUT',
            body: JSON.stringify({ quantity: newQty })
        });

        if (res.ok) {
            loadCart();
            fetchNavbarCounts();
        } else {
            showAlert('cart-alert', res.data?.message || 'Failed to update item quantity.', 'danger');
        }
    }

    async function removeCartItem(itemId) {
        if (!confirm('Remove this item from your cart?')) return;

        const res = await apiFetch(`/api/cart/items/${itemId}`, {
            method: 'DELETE'
        });

        if (res.ok) {
            showAlert('cart-alert', 'Item removed from cart.', 'success');
            loadCart();
            fetchNavbarCounts();
        } else {
            showAlert('cart-alert', res.data?.message || 'Failed to delete item.', 'danger');
        }
    }

    async function clearWholeCart() {
        if (!confirm('Are you sure you want to clear your entire cart?')) return;

        const res = await apiFetch('/api/cart', {
            method: 'DELETE'
        });

        if (res.ok) {
            showAlert('cart-alert', 'Shopping cart cleared.', 'success');
            loadCart();
            fetchNavbarCounts();
        } else {
            showAlert('cart-alert', res.data?.message || 'Failed to clear cart.', 'danger');
        }
    }

    function proceedToCheckout() {
        window.location.href = '/checkout';
    }

    document.addEventListener('DOMContentLoaded', loadCart);
</script>
@endpush
