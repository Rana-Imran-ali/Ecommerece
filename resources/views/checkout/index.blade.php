@extends('layouts.app')

@section('title', 'Checkout - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Breadcrumb / Header -->
    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Order Checkout</h1>
            <p class="text-sm text-gray-500">Confirm address, choose payment, and complete your order.</p>
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
<!-- Stripe.js v3 -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    const STRIPE_PK = '{{ config("services.stripe.key") }}';

    let checkoutCart   = null;
    let userAddresses  = [];
    let appliedCoupon  = null;
    let stripeInstance = null;
    let cardElement    = null;
    let currentPaymentIntentClientSecret = null;
    let currentPaymentIntentId           = null;

    // ── Initialise Stripe.js once ──────────────────────────────────────────
    try {
        stripeInstance = Stripe(STRIPE_PK);
    } catch (e) {
        console.warn('Stripe.js not available:', e.message);
    }

    // ── Page init ──────────────────────────────────────────────────────────
    async function initCheckout() {
        if (!getAuthToken()) {
            window.location.href = '/login?redirect=/checkout';
            return;
        }

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

        checkoutCart  = cartRes.data.data;
        userAddresses = addrRes.ok ? (addrRes.data.data || []) : [];

        renderCheckoutUI();
    }

    // ── Render the full checkout page ──────────────────────────────────────
    function renderCheckoutUI() {
        const subtotal   = parseFloat(checkoutCart.subtotal || 0);
        const discount   = appliedCoupon ? parseFloat(appliedCoupon.discount_amount || 0) : 0;
        const finalTotal = Math.max(0, subtotal - discount).toFixed(2);
        const userEmail  = (getAuthUser()?.email || '').trim();

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

                <!-- 2. Order Notification Email -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h2 class="text-base font-bold text-gray-900">2. Order Notification Email</h2>
                        <span class="text-xs text-indigo-600 font-medium flex items-center gap-1">
                            <span>📧</span> Automated Updates
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        We will automatically send real-time order tracking updates (<strong>Pending, Confirmed/Processing, Out for Delivery, and Delivered</strong>) including the <strong>expected delivery date</strong> to this email address.
                    </p>
                    <div>
                        <label for="customer-email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Recipient Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="customer-email"
                               name="customer_email"
                               value="${userEmail}"
                               required
                               placeholder="your.email@example.com"
                               class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        <p class="text-[11px] text-gray-400 mt-1">Pre-filled with your account email. You may change it to receive notifications at an alternate address.</p>
                    </div>
                </div>

                <!-- 3. Payment Method -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
                    <h2 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">3. Payment Method</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label id="pm-label-cod"
                               class="flex items-center p-3 border-2 border-indigo-600 bg-indigo-50/20 rounded-lg cursor-pointer transition-all">
                            <input type="radio" id="pm-cod" name="payment_method" value="cod" checked
                                   class="text-indigo-600 focus:ring-indigo-500" onchange="onPaymentMethodChange()">
                            <span class="ml-2.5 text-sm font-semibold text-gray-800">💵 Cash on Delivery</span>
                        </label>
                        <label id="pm-label-card"
                               class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-all">
                            <input type="radio" id="pm-card" name="payment_method" value="card"
                                   class="text-indigo-600 focus:ring-indigo-500" onchange="onPaymentMethodChange()">
                            <span class="ml-2.5 text-sm font-semibold text-gray-800">💳 Credit / Debit Card</span>
                        </label>
                        <label id="pm-label-bank"
                               class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-all">
                            <input type="radio" id="pm-bank" name="payment_method" value="bank_transfer"
                                   class="text-indigo-600 focus:ring-indigo-500" onchange="onPaymentMethodChange()">
                            <span class="ml-2.5 text-sm font-semibold text-gray-800">🏦 Bank Transfer</span>
                        </label>
                    </div>

                    <!-- Card Details Panel -->
                    <div id="card-details-panel" class="hidden mt-4 space-y-4 bg-gradient-to-br from-slate-50 to-indigo-50/30 border border-indigo-100 rounded-xl p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-gray-800">🔒 Secure Card Details</h3>
                            <div class="flex items-center space-x-2 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                256-bit SSL
                            </div>
                        </div>

                        <!-- Card Panel Alert Banner (Inline) -->
                        <div id="card-panel-alert" class="hidden"></div>

                        <!-- Cardholder Name -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Cardholder Full Name <span class="text-red-500">*</span></label>
                            <input type="text" id="cardholder-name"
                                   placeholder="Name as it appears on card"
                                   oninput="clearPaymentErrors()"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                            <p id="cardholder-name-error" class="text-red-600 text-xs mt-1 hidden"></p>
                        </div>

                        <!-- Stripe Card Element -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Card Number, Expiry &amp; CVV <span class="text-red-500">*</span></label>
                            <div id="stripe-card-element"
                                 class="px-3 py-3 border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-indigo-400 transition min-h-[42px]"></div>
                            <div id="card-errors" class="text-red-600 text-xs mt-2 hidden"></div>
                        </div>

                        <!-- Accepted cards -->
                        <div class="flex items-center space-x-2 text-xs text-gray-400">
                            <span>Accepted:</span>
                            <span class="font-bold text-gray-600">VISA</span>
                            <span class="font-bold text-gray-600">Mastercard</span>
                            <span class="font-bold text-gray-600">Amex</span>
                            <span class="font-bold text-gray-600">Discover</span>
                        </div>

                        <!-- Processing status -->
                        <div id="card-processing-status" class="hidden text-xs text-indigo-600 font-medium animate-pulse">
                            🔐 Verifying card securely...
                        </div>
                    </div>

                    <!-- Bank Transfer Details Panel -->
                    <div id="bank-details-panel" class="hidden mt-4 space-y-4 bg-gradient-to-br from-emerald-50 to-teal-50/30 border border-emerald-100 rounded-xl p-5">
                        <!-- Store Bank Info -->
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 mb-3">🏦 Transfer to Our Bank Account</h3>
                            <div class="bg-white rounded-lg border border-emerald-200 p-4 space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-gray-500">Bank Name:</span><span class="font-bold text-gray-800">First National Bank</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Account Title:</span><span class="font-bold text-gray-800">EStore Payments LLC</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Account No.:</span><span class="font-mono font-bold text-gray-800 select-all">1234-5678-9012</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Routing / SWIFT:</span><span class="font-mono font-bold text-gray-800">FNBAUS33</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Amount:</span><span id="bank-amount-display" class="font-bold text-emerald-700">$${parseFloat(finalTotal).toFixed(2)}</span></div>
                            </div>
                        </div>

                        <div class="border-t border-emerald-100 pt-4 space-y-3">
                            <p class="text-xs text-gray-500 font-medium">After completing your transfer, enter your payment details below:</p>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Your Sender Bank Name <span class="text-red-500">*</span></label>
                                <input type="text" id="sender-bank" placeholder="e.g. Chase, Wells Fargo"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Account Holder Name <span class="text-red-500">*</span></label>
                                <input type="text" id="sender-name" placeholder="Your full name"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Transaction / Reference ID <span class="text-red-500">*</span></label>
                                <input type="text" id="transaction-reference" placeholder="e.g. TRX-20240910-XYZ123"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Promo Code / Coupon (Optional) -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-gray-900">3. Promo Code / Coupon <span class="ml-1 text-xs font-normal text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Optional</span></h2>
                        <button type="button" onclick="openCouponsModal()" class="text-xs text-indigo-600 font-semibold hover:underline flex items-center gap-1">
                            🏷️ View Available Coupons
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="coupon-code-input"
                               value="${appliedCoupon ? appliedCoupon.code : ''}"
                               placeholder="e.g. WELCOME10, SUPER20"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500 uppercase font-mono">
                        ${appliedCoupon ? `
                            <button type="button" onclick="removeCoupon()" class="px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 text-sm font-semibold rounded-md transition-colors flex items-center space-x-1">
                                <span>✕</span><span>Remove</span>
                            </button>
                        ` : `
                            <button type="button" onclick="applyCoupon()" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-md transition-colors">
                                Apply
                            </button>
                        `}
                    </div>
                    <div id="coupon-status" class="text-xs">
                        ${appliedCoupon
                            ? `<span class="text-green-600 font-medium">✓ Coupon "${appliedCoupon.code}" applied! Saved $${parseFloat(appliedCoupon.discount_amount).toFixed(2)}</span>`
                            : '<span class="text-gray-400">Skip this field if you don\'t have a coupon code.</span>'
                        }
                    </div>
                </div>

                <!-- Available Coupons Modal -->
                <div id="coupons-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[80vh] flex flex-col">
                        <div class="flex items-center justify-between p-5 border-b border-gray-100">
                            <h3 class="text-base font-bold text-gray-900">🏷️ Available Coupons</h3>
                            <button type="button" onclick="closeCouponsModal()" class="text-gray-400 hover:text-gray-700 text-xl font-bold leading-none">&times;</button>
                        </div>
                        <div id="coupons-modal-body" class="overflow-y-auto p-5 space-y-3 flex-1">
                            <div class="text-center text-sm text-gray-400 py-8">
                                <div class="inline-block animate-spin w-5 h-5 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
                                <div>Loading coupons…</div>
                            </div>
                        </div>
                        <div class="p-4 border-t border-gray-100 text-xs text-gray-400 text-center">
                            Click any coupon to apply it instantly.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Order Summary & Place Order Button -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4 sticky top-4">
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
                            <span id="summary-total" class="text-indigo-600">$${finalTotal}</span>
                        </div>
                    </div>

                    <!-- Inline Summary Alert -->
                    <div id="submit-order-alert" class="hidden my-2"></div>

                    <button type="button" id="place-order-btn" onclick="submitOrder()"
                            ${userAddresses.length === 0 ? 'disabled' : ''}
                            class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-bold rounded-md shadow-sm transition-colors">
                        Place Order Now
                    </button>

                    <p class="text-center text-xs text-gray-400">🔒 Secure Checkout · All data encrypted</p>
                </div>
            </div>
        `;

        // Mount Stripe Card Element after rendering
        mountStripeElement();

        // Sync payment method label styling on initial render
        syncPaymentLabels();
    }

    // ── Mount Stripe Card Element ──────────────────────────────────────────
    function mountStripeElement() {
        if (!stripeInstance) return;
        if (cardElement) {
            try { cardElement.unmount(); cardElement.destroy(); } catch(e) {}
            cardElement = null;
        }
        const elements  = stripeInstance.elements();
        cardElement     = elements.create('card', {
            style: {
                base: {
                    fontSize: '14px',
                    color:    '#374151',
                    fontFamily: 'ui-sans-serif, system-ui, sans-serif',
                    '::placeholder': { color: '#9CA3AF' }
                },
                invalid: { color: '#DC2626' }
            },
            hidePostalCode: true
        });
        cardElement.mount('#stripe-card-element');
        cardElement.on('change', function(e) {
            clearPaymentErrors();
            const errDiv = document.getElementById('card-errors');
            if (e.error) {
                const cardBox = document.getElementById('stripe-card-element');
                if (cardBox) cardBox.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                if (errDiv) {
                    errDiv.innerHTML = `
                        <div class="flex items-start gap-2 p-2.5 bg-red-50 border border-red-200 rounded-lg text-red-700 text-xs font-medium">
                            <span class="text-sm leading-none">⚠️</span>
                            <div>${e.error.message}</div>
                        </div>
                    `;
                    errDiv.classList.remove('hidden');
                }
            } else {
                if (errDiv) {
                    errDiv.innerHTML = '';
                    errDiv.classList.add('hidden');
                }
            }
        });
    }

    // ── Format friendly Stripe error messages ──────────────────────────────
    function formatPaymentError(error) {
        if (!error) return 'Payment could not be processed. Please try again.';
        if (typeof error === 'string') return error;

        const declineCode = (error.decline_code || error.code || '').toLowerCase();
        const msg = (error.message || '').toLowerCase();

        if (declineCode === 'insufficient_funds' || msg.includes('insufficient') || msg.includes('balance') || msg.includes('funds')) {
            return '⚠️ Insufficient Balance: Your card does not have sufficient funds to complete this transaction. Please use another card or select another payment method (such as Cash on Delivery or Bank Transfer).';
        }
        if (declineCode === 'card_declined' || msg.includes('declined')) {
            return '❌ Card Declined: Your card issuer or bank declined this payment. Please check your card details, contact your bank, or try another card.';
        }
        if (declineCode === 'expired_card' || msg.includes('expired')) {
            return '❌ Card Expired: The expiration date provided has passed. Please check the card date or try a different card.';
        }
        if (declineCode === 'incorrect_cvc' || msg.includes('security code') || msg.includes('cvc') || msg.includes('cvv')) {
            return '❌ Incorrect Security Code (CVC): The security code entered is incorrect. Please check the back of your card.';
        }
        if (declineCode === 'processing_error' || msg.includes('processing error')) {
            return '⚠️ Processing Error: The payment network experienced an issue. Please wait a moment and try again.';
        }

        return error.message || 'Payment failed. Please check your card details and try again.';
    }

    // ── Display payment error prominently on screen ─────────────────────────
    function showPaymentError(errorMessage, isCardField = true) {
        const formatted = formatPaymentError(errorMessage);

        // 1. Inline below card element
        const cardErrDiv = document.getElementById('card-errors');
        if (cardErrDiv) {
            cardErrDiv.innerHTML = `
                <div class="flex items-start gap-2 p-3 bg-red-50 border border-red-300 rounded-lg text-red-800 text-xs font-semibold shadow-sm animate-pulse">
                    <span class="text-base leading-none">⚠️</span>
                    <div class="flex-1">${formatted}</div>
                </div>
            `;
            cardErrDiv.classList.remove('hidden');
        }

        // 2. Red border around card element
        const cardBox = document.getElementById('stripe-card-element');
        if (cardBox && isCardField) {
            cardBox.classList.add('border-red-500', 'ring-2', 'ring-red-200');
        }

        // 3. Banner inside card panel
        const cardPanelAlert = document.getElementById('card-panel-alert');
        if (cardPanelAlert) {
            cardPanelAlert.innerHTML = `
                <div class="p-3.5 bg-red-50 border-l-4 border-red-600 text-red-900 rounded-r text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>⚠️</span>
                        <span>${formatted}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="ml-3 font-bold opacity-60 hover:opacity-100 text-base">&times;</button>
                </div>
            `;
            cardPanelAlert.classList.remove('hidden');
        }

        // 4. Banner in summary next to Place Order button
        const summaryAlert = document.getElementById('submit-order-alert');
        if (summaryAlert) {
            summaryAlert.innerHTML = `
                <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-xs font-semibold">
                    ${formatted}
                </div>
            `;
            summaryAlert.classList.remove('hidden');
        }

        // 5. Top level alert
        showAlert('checkout-alert', formatted, 'danger');

        // 6. Smoothly scroll directly to the error
        const scrollTarget = document.getElementById('card-panel-alert') || document.getElementById('card-errors') || document.getElementById('submit-order-alert');
        if (scrollTarget) {
            scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // ── Clear all payment errors ───────────────────────────────────────────
    function clearPaymentErrors() {
        const cardErrDiv = document.getElementById('card-errors');
        if (cardErrDiv) {
            cardErrDiv.innerHTML = '';
            cardErrDiv.classList.add('hidden');
        }
        const cardBox = document.getElementById('stripe-card-element');
        if (cardBox) {
            cardBox.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
        }
        const cardPanelAlert = document.getElementById('card-panel-alert');
        if (cardPanelAlert) {
            cardPanelAlert.innerHTML = '';
            cardPanelAlert.classList.add('hidden');
        }
        const summaryAlert = document.getElementById('submit-order-alert');
        if (summaryAlert) {
            summaryAlert.innerHTML = '';
            summaryAlert.classList.add('hidden');
        }
        const nameInput = document.getElementById('cardholder-name');
        if (nameInput) {
            nameInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
        }
        const nameErr = document.getElementById('cardholder-name-error');
        if (nameErr) {
            nameErr.innerHTML = '';
            nameErr.classList.add('hidden');
        }
    }

    // ── Show Success Modal ────────────────────────────────────────────────
    function showPaymentSuccessModal(orderId) {
        const modal = document.createElement('div');
        modal.id = 'payment-success-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl p-8 max-w-md w-full text-center shadow-2xl border border-green-100 transform transition-all scale-100 space-y-4">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-3xl font-black shadow-inner animate-pulse">
                    ✓
                </div>
                <h3 class="text-xl font-extrabold text-gray-900">Payment Confirmed!</h3>
                <p class="text-sm text-gray-600">Your payment has been successfully processed and order <strong>#${orderId}</strong> is confirmed.</p>
                <div class="inline-flex items-center text-xs font-semibold text-indigo-600 gap-2">
                    <span class="w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></span>
                    Redirecting to your order summary...
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // ── Payment method change ──────────────────────────────────────────────
    function onPaymentMethodChange() {
        syncPaymentLabels();
        const method = getSelectedPaymentMethod();

        document.getElementById('card-details-panel').classList.toggle('hidden', method !== 'card');
        document.getElementById('bank-details-panel').classList.toggle('hidden', method !== 'bank_transfer');

        const btn = document.getElementById('place-order-btn');
        if (method === 'card')          btn.textContent = 'Pay & Place Order';
        else if (method === 'bank_transfer') btn.textContent = 'Submit Transfer & Place Order';
        else                            btn.textContent = 'Place Order (Pay on Delivery)';

        // Reset payment intent when method changes
        currentPaymentIntentClientSecret = null;
        currentPaymentIntentId           = null;
    }

    function syncPaymentLabels() {
        const method = getSelectedPaymentMethod();
        ['cod', 'card', 'bank'].forEach(id => {
            const label = document.getElementById(`pm-label-${id}`);
            if (!label) return;
            const val   = id === 'bank' ? 'bank_transfer' : id;
            if (val === method) {
                label.classList.remove('border-gray-200');
                label.classList.add('border-indigo-600', 'bg-indigo-50/20');
            } else {
                label.classList.remove('border-indigo-600', 'bg-indigo-50/20');
                label.classList.add('border-gray-200');
            }
        });
    }

    function getSelectedPaymentMethod() {
        const inp = document.querySelector('input[name="payment_method"]:checked');
        return inp ? inp.value : 'cod';
    }

    // ── Apply / Remove Coupon ─────────────────────────────────────────────
    async function applyCoupon() {
        const input = document.getElementById('coupon-code-input');
        const code  = input ? input.value.trim() : '';

        if (!code) {
            showAlert('checkout-alert', 'Please enter a coupon code.', 'warning');
            return;
        }

        const addrInput = document.querySelector('input[name="selected_address"]:checked');
        const res = await apiFetch('/api/coupons/validate', {
            method: 'POST',
            body: JSON.stringify({
                code:         code,
                order_amount: checkoutCart.subtotal
            })
        });

        if (res.ok) {
            appliedCoupon = { ...res.data?.data, code: code };
            showAlert('checkout-alert', res.data?.message || 'Coupon applied!', 'success');
            // Reset payment intent so new amount is recalculated
            currentPaymentIntentClientSecret = null;
            currentPaymentIntentId           = null;
            renderCheckoutUI();
        } else {
            showAlert('checkout-alert', res.data?.message || 'Invalid coupon.', 'danger');
        }
    }

    function removeCoupon() {
        appliedCoupon = null;
        currentPaymentIntentClientSecret = null;
        currentPaymentIntentId           = null;
        renderCheckoutUI();
        showAlert('checkout-alert', 'Coupon removed.', 'info');
    }

    // ── Place Order (main handler) ─────────────────────────────────────────
    async function submitOrder() {
        clearPaymentErrors();

        const addrInput = document.querySelector('input[name="selected_address"]:checked');
        if (!addrInput) {
            showPaymentError('Please select or add a delivery address before proceeding.', false);
            return;
        }

        const emailInput = document.getElementById('customer-email');
        const customerEmail = emailInput?.value?.trim() || getAuthUser()?.email;
        if (!customerEmail || !customerEmail.includes('@')) {
            showPaymentError('Please provide a valid order notification email address.', false);
            return;
        }

        const paymentMethod = getSelectedPaymentMethod();
        const btn           = document.getElementById('place-order-btn');

        btn.disabled    = true;
        btn.textContent = '⟳ Processing...';

        try {
            if (paymentMethod === 'card') {
                await handleCardCheckout(addrInput, btn, customerEmail);
            } else if (paymentMethod === 'bank_transfer') {
                await handleBankTransferCheckout(addrInput, btn, customerEmail);
            } else {
                await handleCodCheckout(addrInput, btn, customerEmail);
            }
        } catch (err) {
            btn.disabled    = false;
            const method = getSelectedPaymentMethod();
            if (method === 'card')               btn.textContent = 'Pay & Place Order';
            else if (method === 'bank_transfer') btn.textContent = 'Submit Transfer & Place Order';
            else                                 btn.textContent = 'Place Order (Pay on Delivery)';

            showPaymentError(err.message || 'An unexpected error occurred.', method === 'card');
        }
    }

    // ── Card Checkout ──────────────────────────────────────────────────────
    async function handleCardCheckout(addrInput, btn, customerEmail) {
        clearPaymentErrors();

        if (!stripeInstance || !cardElement) {
            showPaymentError('Payment system unavailable. Please refresh the page and try again.', false);
            throw new Error('Payment system unavailable. Please refresh and try again.');
        }

        const cardholderInput = document.getElementById('cardholder-name');
        const cardholderName  = cardholderInput?.value?.trim();
        if (!cardholderName) {
            btn.disabled    = false;
            btn.textContent = 'Pay & Place Order';
            if (cardholderInput) {
                cardholderInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                cardholderInput.focus();
            }
            const nameErr = document.getElementById('cardholder-name-error');
            if (nameErr) {
                nameErr.textContent = 'Please enter the cardholder full name.';
                nameErr.classList.remove('hidden');
            }
            showPaymentError('Please enter the cardholder full name as it appears on the card.', false);
            return;
        }

        // Step 1: Get / reuse PaymentIntent client_secret from server
        if (!currentPaymentIntentClientSecret) {
            btn.textContent = '🔐 Initialising payment...';

            const intentRes = await apiFetch('/api/stripe/payment-intent', {
                method: 'POST',
                body:   JSON.stringify({
                    address_id:     parseInt(addrInput.value),
                    coupon_code:    appliedCoupon ? appliedCoupon.code : null,
                    customer_email: customerEmail,
                })
            });

            if (!intentRes.ok) {
                const msg = intentRes.data?.message || 'Failed to initialise payment.';
                showPaymentError(msg, false);
                throw new Error(msg);
            }

            currentPaymentIntentClientSecret = intentRes.data.client_secret;
            currentPaymentIntentId           = intentRes.data.intent_id;
        }

        // Step 2: Confirm the card payment via Stripe.js
        btn.textContent = '🔐 Verifying card & balance...';
        document.getElementById('card-processing-status').classList.remove('hidden');

        const { paymentIntent, error } = await stripeInstance.confirmCardPayment(
            currentPaymentIntentClientSecret,
            {
                payment_method: {
                    card:            cardElement,
                    billing_details: { name: cardholderName, email: customerEmail }
                }
            }
        );

        document.getElementById('card-processing-status').classList.add('hidden');

        if (error) {
            btn.disabled    = false;
            btn.textContent = 'Pay & Place Order';
            // Reset intent so next attempt creates a fresh PaymentIntent
            currentPaymentIntentClientSecret = null;
            currentPaymentIntentId           = null;

            showPaymentError(error, true);
            return;
        }

        if (!paymentIntent || paymentIntent.status !== 'succeeded') {
            btn.disabled    = false;
            btn.textContent = 'Pay & Place Order';
            showPaymentError('Payment was not completed. Please check your card balance and try again.', true);
            return;
        }

        // Step 3: Create the order, referencing the confirmed PaymentIntent
        btn.textContent = '⟳ Confirming order...';

        const orderRes = await apiFetch('/api/orders', {
            method: 'POST',
            body:   JSON.stringify({
                address_id:              parseInt(addrInput.value),
                customer_email:          customerEmail,
                payment_method:          'card',
                coupon_code:             appliedCoupon ? appliedCoupon.code : null,
                stripe_payment_intent_id: paymentIntent.id,
            })
        });

        if (orderRes.ok && orderRes.data?.data?.id) {
            btn.textContent = '✓ Payment Confirmed!';
            btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');

            fetchNavbarCounts();
            showPaymentSuccessModal(orderRes.data.data.id);
            setTimeout(() => window.location.href = `/orders/${orderRes.data.data.id}`, 1400);
        } else {
            btn.disabled    = false;
            btn.textContent = 'Pay & Place Order';
            const failMsg = orderRes.data?.message || 'Order creation failed after payment. Please contact support.';
            showPaymentError(failMsg, false);
            throw new Error(failMsg);
        }
    }

    // ── Bank Transfer Checkout ─────────────────────────────────────────────
    async function handleBankTransferCheckout(addrInput, btn, customerEmail) {
        const senderBank = document.getElementById('sender-bank')?.value?.trim();
        const senderName = document.getElementById('sender-name')?.value?.trim();
        const txRef      = document.getElementById('transaction-reference')?.value?.trim();

        if (!senderBank || !senderName || !txRef) {
            btn.disabled    = false;
            btn.textContent = 'Submit Transfer & Place Order';
            showAlert('checkout-alert', 'Please fill in all bank transfer details (Sender Bank, Account Holder Name, and Transaction Reference).', 'danger');
            return;
        }

        const res = await apiFetch('/api/orders', {
            method: 'POST',
            body:   JSON.stringify({
                address_id:           parseInt(addrInput.value),
                customer_email:       customerEmail,
                payment_method:       'bank_transfer',
                coupon_code:          appliedCoupon ? appliedCoupon.code : null,
                sender_bank:          senderBank,
                sender_name:          senderName,
                transaction_reference: txRef,
            })
        });

        btn.disabled    = false;
        btn.textContent = 'Submit Transfer & Place Order';

        if (res.ok && res.data?.data?.id) {
            fetchNavbarCounts();
            showAlert('checkout-alert', '✓ Order placed! Your transfer reference has been recorded. We\'ll verify and process your order soon.', 'success');
            setTimeout(() => window.location.href = `/orders/${res.data.data.id}`, 1200);
        } else {
            throw new Error(res.data?.message || 'Failed to place order.');
        }
    }

    // ── COD Checkout ──────────────────────────────────────────────────────
    async function handleCodCheckout(addrInput, btn, customerEmail) {
        const res = await apiFetch('/api/orders', {
            method: 'POST',
            body:   JSON.stringify({
                address_id:     parseInt(addrInput.value),
                customer_email: customerEmail,
                payment_method: 'cod',
                coupon_code:    appliedCoupon ? appliedCoupon.code : null,
            })
        });

        btn.disabled    = false;
        btn.textContent = 'Place Order (Pay on Delivery)';

        if (res.ok && res.data?.data?.id) {
            fetchNavbarCounts();
            showAlert('checkout-alert', '✓ Order placed! Pay on delivery. Redirecting...', 'success');
            setTimeout(() => window.location.href = `/orders/${res.data.data.id}`, 800);
        } else {
            throw new Error(res.data?.message || 'Failed to place order.');
        }
    }

    document.addEventListener('DOMContentLoaded', initCheckout);

    // ── Available Coupons Modal ────────────────────────────────────────────
    async function openCouponsModal() {
        const modal = document.getElementById('coupons-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        const body = document.getElementById('coupons-modal-body');
        body.innerHTML = `
            <div class="text-center text-sm text-gray-400 py-8">
                <div class="inline-block animate-spin w-5 h-5 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
                <div>Loading coupons…</div>
            </div>
        `;

        try {
            const res = await apiFetch('/api/coupons');
            const coupons = res.data?.data || [];

            if (!coupons.length) {
                body.innerHTML = `<div class="text-center text-sm text-gray-400 py-8">No active coupons available right now.</div>`;
                return;
            }

            const subtotal = parseFloat(checkoutCart?.subtotal || 0);

            body.innerHTML = coupons.map(c => {
                const minOrder    = parseFloat(c.min_order_amount || 0);
                const eligible    = subtotal >= minOrder;
                const discount    = c.discount_type === 'percent'
                    ? `${parseFloat(c.discount_percent || 0).toFixed(0)}% OFF`
                    : `$${parseFloat(c.discount_amount || 0).toFixed(2)} OFF`;
                const expiry      = c.expires_at ? `Expires ${new Date(c.expires_at).toLocaleDateString()}` : 'No expiry';
                const minNote     = minOrder > 0 ? `Min. order $${minOrder.toFixed(2)}` : 'No minimum';
                const usageLeft   = (c.max_uses != null && c.used_count != null)
                    ? `${c.max_uses - c.used_count} uses left`
                    : '';

                return `
                    <button type="button"
                            onclick="selectCoupon('${c.code}')"
                            ${!eligible ? 'disabled title="Minimum order not met"' : ''}
                            class="w-full text-left p-4 rounded-xl border-2 ${eligible ? 'border-indigo-200 hover:border-indigo-500 hover:bg-indigo-50/30 cursor-pointer' : 'border-gray-100 bg-gray-50 opacity-50 cursor-not-allowed'} transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-mono font-bold text-gray-800 text-sm">${c.code}</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full ${eligible ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">${discount}</span>
                        </div>
                        <div class="text-xs text-gray-500 flex flex-wrap gap-2">
                            <span>${minNote}</span>
                            ${usageLeft ? `<span>·</span><span>${usageLeft}</span>` : ''}
                            <span>·</span><span>${expiry}</span>
                        </div>
                    </button>
                `;
            }).join('');
        } catch(e) {
            body.innerHTML = `<div class="text-center text-sm text-red-500 py-8">Failed to load coupons. Please try again.</div>`;
        }
    }

    function closeCouponsModal() {
        const modal = document.getElementById('coupons-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function selectCoupon(code) {
        closeCouponsModal();
        const inp = document.getElementById('coupon-code-input');
        if (inp) inp.value = code;
        await applyCoupon();
    }

    // Close modal on backdrop click
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('coupons-modal');
        if (modal && e.target === modal) closeCouponsModal();
    });
</script>
@endpush
