@extends('layouts.app')

@section('title', 'Checkout - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Breadcrumb / Header -->
    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Order Checkout</h1>
            <p class="text-sm text-gray-500">Confirm shipping address, apply promo coupons, and complete your order.</p>
        </div>
        <a href="{{ url('/cart') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
            &larr; Back to Cart
        </a>
    </div>

    <!-- Alert Container -->
    <div id="checkout-alert"></div>

    <!-- Checkout Main Grid -->
    <div id="checkout-content" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="col-span-full py-16 text-center text-gray-500 bg-white rounded-lg border border-gray-200">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
            <div>Preparing checkout details...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let checkoutCart = null;
    let userAddresses = [];
    let appliedCoupon = null;

    async function initCheckout() {
        if (!getAuthToken()) {
            window.location.href = '/login?redirect=/checkout';
            return;
        }

        // Fetch Cart & Addresses in parallel
        const [cartRes, addrRes] = await Promise.all([
            apiFetch('/api/cart'),
            apiFetch('/api/addresses')
        ]);

        if (!cartRes.ok || !cartRes.data?.data?.items?.length) {
            document.getElementById('checkout-content').innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-lg border border-gray-200 space-y-3">
                    <p class="text-lg font-semibold text-gray-800">Your cart is empty</p>
                    <p class="text-sm text-gray-500">Add products to your cart before proceeding to checkout.</p>
                    <a href="/products" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Browse Products</a>
                </div>
            `;
            return;
        }

        checkoutCart = cartRes.data.data;
        userAddresses = addrRes.ok ? (addrRes.data.data || []) : [];

        renderCheckoutUI();
    }

    function renderCheckoutUI() {
        const subtotal = parseFloat(checkoutCart.subtotal || 0);
        const discount = appliedCoupon ? parseFloat(appliedCoupon.discount_amount || 0) : 0;
        const finalTotal = Math.max(0, subtotal - discount).toFixed(2);

        const container = document.getElementById('checkout-content');

        container.innerHTML = `
            <!-- Left 2 Cols: Address, Payment, Coupon -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Shipping Address -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h2 class="text-base font-bold text-gray-900">1. Select Delivery Address</h2>
                        <a href="/addresses" class="text-xs text-indigo-600 hover:underline">+ Manage Addresses</a>
                    </div>

                    ${userAddresses.length === 0 ? `
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                            No saved addresses found. <a href="/addresses" class="font-bold underline">Add an address</a> to proceed.
                        </div>
                    ` : `
                        <div class="space-y-3">
                            ${userAddresses.map((addr, idx) => {
                                const isChecked = Boolean(addr.is_default) || idx === 0;
                                return `
                                    <label class="flex items-start p-3.5 border rounded-lg cursor-pointer transition-colors ${isChecked ? 'border-indigo-600 bg-indigo-50/20' : 'border-gray-200 hover:bg-gray-50'}">
                                        <input type="radio" name="selected_address" value="${addr.id}" ${isChecked ? 'checked' : ''} class="mt-1 text-indigo-600 focus:ring-indigo-500">
                                        <div class="ml-3 text-sm">
                                            <div class="font-bold text-gray-900 flex items-center space-x-2">
                                                <span>${addr.name}</span>
                                                ${addr.is_default ? '<span class="px-1.5 py-0.2 text-[10px] bg-indigo-100 text-indigo-800 font-semibold rounded">DEFAULT</span>' : ''}
                                            </div>
                                            <p class="text-gray-600 mt-0.5">${addr.address_line1}${addr.address_line2 ? ', ' + addr.address_line2 : ''}, ${addr.city}, ${addr.state} ${addr.postal_code}</p>
                                            <p class="text-xs text-gray-400">Phone: ${addr.phone}</p>
                                        </div>
                                    </label>
                                `;
                            }).join('')}
                        </div>
                    `}
                </div>

                <!-- 2. Payment Method -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
                    <h2 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">2. Payment Method</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="cod" checked class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2.5 text-sm font-semibold text-gray-800">Cash on Delivery</span>
                        </label>
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="card" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2.5 text-sm font-semibold text-gray-800">Credit / Debit Card</span>
                        </label>
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="bank_transfer" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2.5 text-sm font-semibold text-gray-800">Bank Transfer</span>
                        </label>
                    </div>
                </div>

                <!-- 3. Promo Coupon -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-3">
                    <h2 class="text-base font-bold text-gray-900">3. Apply Promo Code / Coupon</h2>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="coupon-code-input"
                               value="${appliedCoupon ? appliedCoupon.code : ''}"
                               placeholder="e.g. WELCOME10, SUPER20"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500 uppercase font-mono">
                        <button type="button" onclick="applyCoupon()" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-md transition-colors">
                            Apply
                        </button>
                    </div>
                    <div id="coupon-status" class="text-xs">
                        ${appliedCoupon ? `<span class="text-green-600 font-medium">Coupon "${appliedCoupon.code}" applied! Saved $${discount.toFixed(2)}</span>` : '<span class="text-gray-400">Try codes: WELCOME10 (10% off), SUPER20 (20% off)</span>'}
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Summary & Place Order -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
                    <h2 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">Order Summary</h2>

                    <!-- Items Preview -->
                    <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 pr-1">
                        ${checkoutCart.items.map(item => `
                            <div class="py-2 flex items-center justify-between text-xs">
                                <span class="text-gray-800 font-medium truncate max-w-[150px]">${item.product?.name || 'Product'} &times; ${item.quantity}</span>
                                <span class="text-gray-900 font-bold">$${(parseFloat(item.product?.price || 0) * item.quantity).toFixed(2)}</span>
                            </div>
                        `).join('')}
                    </div>

                    <div class="border-t border-gray-100 pt-3 space-y-2 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="font-semibold text-gray-900">$${subtotal.toFixed(2)}</span>
                        </div>
                        ${discount > 0 ? `
                            <div class="flex justify-between text-green-600 font-medium">
                                <span>Discount (${appliedCoupon.code}):</span>
                                <span>-$${discount.toFixed(2)}</span>
                            </div>
                        ` : ''}
                        <div class="flex justify-between">
                            <span>Shipping:</span>
                            <span class="text-green-600 font-semibold">FREE</span>
                        </div>
                        <div class="border-t border-gray-100 pt-2 flex justify-between text-base font-bold text-gray-900">
                            <span>Grand Total:</span>
                            <span class="text-indigo-600">$${finalTotal}</span>
                        </div>
                    </div>

                    <button type="button" id="place-order-btn" onclick="submitOrder()"
                            ${userAddresses.length === 0 ? 'disabled' : ''}
                            class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-bold rounded-md shadow-sm transition-colors">
                        Place Order Now
                    </button>
                </div>
            </div>
        `;
    }

    async function applyCoupon() {
        const input = document.getElementById('coupon-code-input');
        const code = input ? input.value.trim() : '';

        if (!code) {
            showAlert('checkout-alert', 'Please enter a coupon code.', 'warning');
            return;
        }

        const res = await apiFetch('/api/coupons/validate', {
            method: 'POST',
            body: JSON.stringify({
                code: code,
                order_amount: checkoutCart.subtotal
            })
        });

        if (res.ok) {
            appliedCoupon = res.data?.data;
            showAlert('checkout-alert', res.data?.message || 'Coupon applied!', 'success');
            renderCheckoutUI();
        } else {
            showAlert('checkout-alert', res.data?.message || 'Invalid coupon.', 'danger');
        }
    }

    async function submitOrder() {
        const addrInput = document.querySelector('input[name="selected_address"]:checked');
        if (!addrInput) {
            showAlert('checkout-alert', 'Please select or add a shipping address.', 'danger');
            return;
        }

        const payInput = document.querySelector('input[name="payment_method"]:checked');
        const payment_method = payInput ? payInput.value : 'cod';

        const btn = document.getElementById('place-order-btn');
        btn.disabled = true;
        btn.innerHTML = `<span class="inline-block animate-spin mr-2">⟳</span> Placing Order...`;

        const payload = {
            address_id: parseInt(addrInput.value),
            payment_method: payment_method,
            coupon_code: appliedCoupon ? appliedCoupon.code : null,
        };

        const res = await apiFetch('/api/orders', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        btn.disabled = false;
        btn.textContent = 'Place Order Now';

        if (res.ok && res.data?.data?.id) {
            fetchNavbarCounts();
            showAlert('checkout-alert', 'Order placed successfully! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = `/orders/${res.data.data.id}`;
            }, 600);
        } else {
            showAlert('checkout-alert', res.data?.message || 'Failed to place order.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', initCheckout);
</script>
@endpush
