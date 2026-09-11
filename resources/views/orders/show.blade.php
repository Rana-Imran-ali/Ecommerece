@extends('layouts.app')

@section('title', 'Order Details - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <div class="flex items-center justify-between text-sm">
        <a href="{{ url('/orders') }}" class="text-indigo-600 hover:underline">&larr; Back to All Orders</a>
        <span class="text-gray-400">Order ID: #{{ $orderId }}</span>
    </div>

    <!-- Alert Container -->
    <div id="order-detail-alert"></div>

    <!-- Main Receipt Container -->
    <div id="order-card" class="bg-white rounded-lg border border-gray-200 overflow-hidden p-6 sm:p-8 space-y-6">
        <div class="py-16 text-center text-gray-500">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
            <div>Loading order receipt...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const orderId = {{ $orderId }};

    async function loadOrder() {
        if (!getAuthToken()) {
            window.location.href = '/login?redirect=/orders/' + orderId;
            return;
        }

        const container = document.getElementById('order-card');
        const res = await apiFetch(`/api/orders/${orderId}`);

        if (!res.ok) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <p class="text-lg font-semibold text-red-600">Order not found</p>
                    <p class="text-sm text-gray-500 mt-1">${res.data?.message || 'Access denied or invalid order ID.'}</p>
                    <a href="/orders" class="inline-block mt-4 text-sm text-indigo-600 hover:underline">View my orders</a>
                </div>
            `;
            return;
        }

        const order = res.data?.data;
        if (!order) return;

        const addr = order.address || {};
        const payment = order.payments?.[0] || {};
        const couponUsage = order.coupon_usages?.[0] || null;
        const coupon = couponUsage?.coupon || null;

        const statusColors = {
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            processing: 'bg-blue-100 text-blue-800 border-blue-200',
            out_for_delivery: 'bg-purple-100 text-purple-800 border-purple-200',
            shipped: 'bg-indigo-100 text-indigo-800 border-indigo-200',
            delivered: 'bg-emerald-100 text-emerald-800 border-emerald-200',
            completed: 'bg-green-100 text-green-800 border-green-200',
            cancelled: 'bg-red-100 text-red-800 border-red-200',
        };
        const statusLabels = {
            pending: 'Pending',
            processing: 'Confirmed / Processing',
            out_for_delivery: 'Out for Delivery',
            shipped: 'Out for Delivery',
            delivered: 'Delivered',
            completed: 'Completed',
            cancelled: 'Cancelled',
        };
        const badgeClass = statusColors[order.status] || 'bg-gray-100 text-gray-800 border-gray-200';
        const displayStatus = statusLabels[order.status] || order.status;
        const formattedDeliveryDate = order.expected_delivery_date
            ? new Date(order.expected_delivery_date).toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' })
            : null;

        container.innerHTML = `
            <!-- Receipt Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-gray-900">Order #${order.id}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border uppercase tracking-wider ${badgeClass}">
                            ${displayStatus}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Placed on ${new Date(order.created_at).toLocaleString()}</p>
                    ${order.customer_email ? `
                        <p class="text-xs text-indigo-600 mt-0.5 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Notifications sent to: <span class="font-semibold">${order.customer_email}</span>
                        </p>
                    ` : ''}
                </div>

                ${order.status === 'pending' ? `
                    <button type="button" onclick="cancelOrder()"
                            class="py-2 px-3.5 border border-red-300 text-red-600 hover:bg-red-50 text-xs font-bold rounded-md transition-colors">
                        Cancel Order
                    </button>
                ` : ''}
            </div>

            <!-- Expected Delivery Date Banner (if applicable) -->
            ${formattedDeliveryDate ? `
                <div class="p-4 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-100 rounded-xl flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Expected Delivery</div>
                            <div class="text-base font-bold text-gray-900">${formattedDeliveryDate}</div>
                        </div>
                    </div>
                    <span class="inline-flex items-center text-xs font-medium text-indigo-700 bg-white px-2.5 py-1 rounded-full border border-indigo-200">
                        Status: ${displayStatus}
                    </span>
                </div>
            ` : ''}

            <!-- Shipping & Payment Information Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 space-y-1">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Delivery Address</span>
                    <p class="font-bold text-gray-800">${addr.name || 'Recipient'}</p>
                    <p class="text-gray-600">${addr.address_line1 || ''}${addr.address_line2 ? ', ' + addr.address_line2 : ''}</p>
                    <p class="text-gray-600">${addr.city || ''}, ${addr.state || ''} ${addr.postal_code || ''}</p>
                    <p class="text-gray-600">${addr.country || ''}</p>
                    <p class="text-xs text-gray-400 mt-1">Phone: ${addr.phone || 'N/A'}</p>
                    ${order.customer_email ? `<p class="text-xs text-indigo-600 font-medium pt-1">Email: ${order.customer_email}</p>` : ''}
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 space-y-1">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Payment & Promo</span>
                    <p class="text-gray-700"><span class="font-semibold">Method:</span> <span class="uppercase">${payment.payment_method || 'N/A'}</span></p>
                    <p class="text-gray-700"><span class="font-semibold">Payment Status:</span> <span class="capitalize">${payment.status || 'pending'}</span></p>
                    ${coupon ? `
                        <p class="text-green-700 font-medium pt-1">
                            Promo Applied: <span class="font-mono font-bold">${coupon.code}</span> (${coupon.discount_percent}% off)
                        </p>
                    ` : '<p class="text-gray-400">No coupon applied</p>'}
                </div>
            </div>

            <!-- Items Table -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-gray-800">Purchased Items</h3>
                <div class="divide-y divide-gray-100 border-t border-b border-gray-100">
                    ${(order.items || []).map(item => {
                        const p = item.product || {};
                        const lineTotal = (parseFloat(item.price) * item.quantity).toFixed(2);
                        return `
                            <div class="py-3 flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-bold text-gray-900">${p.name || 'Product'}</p>
                                    <p class="text-xs text-gray-500">Unit Price: $${parseFloat(item.price).toFixed(2)} &times; ${item.quantity} qty</p>
                                </div>
                                <span class="font-bold text-gray-900">$${lineTotal}</span>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>

            <!-- Total Breakdown -->
            <div class="flex justify-end pt-2">
                <div class="w-64 space-y-1.5 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Shipping:</span>
                        <span class="text-green-600 font-medium">Free</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between text-lg font-bold text-gray-900">
                        <span>Grand Total:</span>
                        <span class="text-indigo-600">$${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;
    }

    async function cancelOrder() {
        if (!confirm('Are you sure you want to cancel this order? Product stock will be automatically restored.')) return;

        const res = await apiFetch(`/api/orders/${orderId}/cancel`, {
            method: 'PATCH'
        });

        if (res.ok) {
            showAlert('order-detail-alert', 'Order cancelled successfully and product inventory restored.', 'success');
            loadOrder();
        } else {
            showAlert('order-detail-alert', res.data?.message || 'Failed to cancel order.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', loadOrder);
</script>
@endpush
