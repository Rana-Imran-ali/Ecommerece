@extends('layouts.app')

@section('title', 'Shopping Cart - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Shopping Cart</h1>
            <p class="text-sm text-gray-500">Review your selected items, adjust quantities, and test stock enforcement.</p>
        </div>
        <button type="button" id="clear-cart-btn" onclick="clearWholeCart()" 
                class="hidden text-sm font-medium text-red-600 hover:text-red-800 transition-colors">
            Clear Entire Cart
        </button>
    </div>

    <!-- Alert Container -->
    <div id="cart-alert"></div>

    <!-- Main Content Grid -->
    <div id="cart-wrapper" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Loading State Skeleton -->
        <div class="col-span-full py-16 text-center text-gray-500 bg-white rounded-lg border border-gray-200">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
            <div>Loading your cart...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadCart() {
        if (!getAuthToken()) {
            document.getElementById('cart-wrapper').innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-lg border border-gray-200 space-y-4">
                    <p class="text-lg font-semibold text-gray-800">You must be signed in to view your cart</p>
                    <a href="/login?redirect=/cart" class="inline-block px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                        Sign In Now
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
                <div class="col-span-full p-8 text-center bg-white rounded-lg border border-red-200 text-red-600">
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
                <div class="col-span-full p-16 text-center bg-white rounded-lg border border-gray-200 space-y-4">
                    <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-800">Your cart is empty</h3>
                    <p class="text-sm text-gray-500">Explore products to add items to your shopping cart.</p>
                    <a href="/products" class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        Browse Catalog
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
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden divide-y divide-gray-200">
                    ${items.map(item => {
                        const p = item.product || {};
                        const img = p.primary_image?.image 
                            ? `/storage/${p.primary_image.image}` 
                            : (p.images?.[0]?.image ? `/storage/${p.images[0].image}` : null);
                        const lineTotal = (parseFloat(p.price || 0) * item.quantity).toFixed(2);

                        return `
                            <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                                        ${img ? `<img src="${img}" class="w-full h-full object-cover">` : `<span class="text-xs text-gray-400">No Img</span>`}
                                    </div>
                                    <div>
                                        <a href="/products/${p.id}" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition-colors">
                                            ${p.name || 'Product'}
                                        </a>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            Unit: $${parseFloat(p.price || 0).toFixed(2)} | Stock: ${p.stock ?? 0}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end space-x-6">
                                    <!-- Quantity Controller -->
                                    <div class="flex items-center border border-gray-300 rounded-md">
                                        <button type="button" onclick="updateCartItemQty(${item.id}, ${item.quantity - 1})"
                                                class="px-2.5 py-1 text-gray-600 hover:bg-gray-100 rounded-l transition-colors"
                                                ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                        <span class="px-3 py-1 text-sm font-semibold text-gray-800">${item.quantity}</span>
                                        <button type="button" onclick="updateCartItemQty(${item.id}, ${item.quantity + 1})"
                                                class="px-2.5 py-1 text-gray-600 hover:bg-gray-100 rounded-r transition-colors"
                                                ${item.quantity >= (p.stock || 0) ? 'disabled' : ''}>+</button>
                                    </div>

                                    <!-- Line Price -->
                                    <div class="text-sm font-bold text-gray-900 w-20 text-right">
                                        $${lineTotal}
                                    </div>

                                    <!-- Remove Button -->
                                    <button type="button" onclick="removeCartItem(${item.id})" class="text-gray-400 hover:text-red-600 p-1" title="Remove item">
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
                <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
                    <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">Order Summary</h2>

                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Total Items:</span>
                        <span class="font-semibold text-gray-900">${cart.total_items}</span>
                    </div>

                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal:</span>
                        <span class="font-semibold text-gray-900">$${parseFloat(cart.subtotal).toFixed(2)}</span>
                    </div>

                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Estimated Shipping:</span>
                        <span class="text-green-600 font-semibold">Free</span>
                    </div>

                    <div class="border-t border-gray-100 pt-3 flex justify-between text-base font-bold text-gray-900">
                        <span>Total:</span>
                        <span>$${parseFloat(cart.subtotal).toFixed(2)}</span>
                    </div>

                    <button type="button" onclick="proceedToCheckout()" 
                            class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-md transition-colors mt-4">
                        Proceed to Checkout
                    </button>

                    <p class="text-xs text-center text-gray-400">
                        Backend testing mode: Verified against database inventory.
                    </p>
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
