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

                        <!-- Cardholder Name -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Cardholder Full Name</label>
                            <input type="text" id="cardholder-name"
                                   placeholder="Name as it appears on card"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                        </div>

                        <!-- Stripe Card Element -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Card Number, Expiry &amp; CVV</label>
                            <div id="stripe-card-element"
                                 class="px-3 py-3 border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-indigo-400 transition min-h-[42px]"></div>
                            <div id="card-errors" class="text-red-500 text-xs mt-1.5 hidden"></div>
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
                            🔐 Verifying payment securely...
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
            const errDiv = document.getElementById('card-errors');
            if (e.error) {
                errDiv.textContent = e.error.message;
                errDiv.classList.remove('hidden');
            } else {
                errDiv.textContent = '';
                errDiv.classList.add('hidden');
            }
        });
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
        const addrInput = document.querySelector('input[name="selected_address"]:checked');
        if (!addrInput) {
            showAlert('checkout-alert', 'Please select or add a shipping address.', 'danger');
            return;
        }

        const paymentMethod = getSelectedPaymentMethod();
        const btn           = document.getElementById('place-order-btn');

        btn.disabled    = true;
        btn.textContent = '⟳ Processing...';

        try {
            if (paymentMethod === 'card') {
                await handleCardCheckout(addrInput, btn);
            } else if (paymentMethod === 'bank_transfer') {
                await handleBankTransferCheckout(addrInput, btn);
            } else {
                await handleCodCheckout(addrInput, btn);
            }
        } catch (err) {
            btn.disabled    = false;
            onPaymentMethodChange(); // restore button label
            showAlert('checkout-alert', err.message || 'An unexpected error occurred.', 'danger');
        }
    }

    // ── Card Checkout ──────────────────────────────────────────────────────
    async function handleCardCheckout(addrInput, btn) {
        if (!stripeInstance || !cardElement) {
            throw new Error('Payment system unavailable. Please refresh and try again.');
        }

        const cardholderName = document.getElementById('cardholder-name')?.value?.trim();
        if (!cardholderName) {
            btn.disabled    = false;
            btn.textContent = 'Pay & Place Order';
            showAlert('checkout-alert', 'Please enter the cardholder name.', 'danger');
            return;
        }

        // Step 1: Get / reuse PaymentIntent client_secret from server
        if (!currentPaymentIntentClientSecret) {
            btn.textContent = '🔐 Initialising payment...';

            const intentRes = await apiFetch('/api/stripe/payment-intent', {
                method: 'POST',
                body:   JSON.stringify({
                    address_id:  parseInt(addrInput.value),
                    coupon_code: appliedCoupon ? appliedCoupon.code : null,
                })
            });

            if (!intentRes.ok) {
                throw new Error(intentRes.data?.message || 'Failed to initialise payment.');
            }

            currentPaymentIntentClientSecret = intentRes.data.client_secret;
            currentPaymentIntentId           = intentRes.data.intent_id;
        }

        // Step 2: Confirm the card payment via Stripe.js
        btn.textContent = '🔐 Verifying card...';
        document.getElementById('card-processing-status').classList.remove('hidden');

        const { paymentIntent, error } = await stripeInstance.confirmCardPayment(
            currentPaymentIntentClientSecret,
            {
                payment_method: {
                    card:            cardElement,
                    billing_details: { name: cardholderName }
                }
            }
        );

        document.getElementById('card-processing-status').classList.add('hidden');

        if (error) {
            btn.disabled    = false;
            btn.textContent = 'Pay & Place Order';
            // Reset so next attempt creates a fresh PaymentIntent
            currentPaymentIntentClientSecret = null;
            currentPaymentIntentId           = null;
            showAlert('checkout-alert', error.message || 'Card payment failed. Please try again.', 'danger');
            return;
        }

        if (paymentIntent.status !== 'succeeded') {
            btn.disabled    = false;
            btn.textContent = 'Pay & Place Order';
            throw new Error('Payment not completed. Please try again.');
        }

        // Step 3: Create the order, referencing the confirmed PaymentIntent
        btn.textContent = '⟳ Creating your order...';

        const orderRes = await apiFetch('/api/orders', {
            method: 'POST',
            body:   JSON.stringify({
                address_id:              parseInt(addrInput.value),
                payment_method:          'card',
                coupon_code:             appliedCoupon ? appliedCoupon.code : null,
                stripe_payment_intent_id: paymentIntent.id,
            })
        });

        btn.disabled    = false;
        btn.textContent = 'Pay & Place Order';

        if (orderRes.ok && orderRes.data?.data?.id) {
            fetchNavbarCounts();
            showAlert('checkout-alert', '✓ Payment successful! Order confirmed. Redirecting...', 'success');
            setTimeout(() => window.location.href = `/orders/${orderRes.data.data.id}`, 900);
        } else {
            throw new Error(orderRes.data?.message || 'Order creation failed after payment. Please contact support.');
        }
    }

    // ── Bank Transfer Checkout ─────────────────────────────────────────────
    async function handleBankTransferCheckout(addrInput, btn) {
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
    async function handleCodCheckout(addrInput, btn) {
        const res = await apiFetch('/api/orders', {
            method: 'POST',
            body:   JSON.stringify({
                address_id:     parseInt(addrInput.value),
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
</script>
@endpush
