@extends('layouts.app')

@section('title', 'Order Details - ' . config('app.name', 'EStore'))
@section('meta_description', 'Detailed order receipt and shipment tracker.')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Breadcrumb & Order Status -->
    <div class="flex items-center justify-between text-xs font-semibold">
        <a href="{{ url('/orders') }}" class="inline-flex items-center gap-1.5 text-navy-900 hover:text-blue-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to All Orders
        </a>
        <span class="font-mono text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">Order Reference: #{{ $orderId }}</span>
    </div>

    <!-- Alert Container -->
    <div id="order-detail-alert"></div>

    <!-- Main Receipt Container -->
    <div id="order-card" class="bg-white rounded-3xl border border-navy-100 shadow-xl shadow-navy-950/5 overflow-hidden p-6 sm:p-8 space-y-6">
        <div class="py-16 text-center text-slate-500">
            <div class="inline-block animate-spin w-8 h-8 border-3 border-navy-900 border-t-transparent rounded-full mb-3"></div>
            <div class="text-sm font-semibold text-slate-700">Loading order receipt...</div>
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
                    <div class="w-12 h-12 mx-auto rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <p class="text-base font-bold text-slate-900">Order not found</p>
                    <p class="text-xs text-slate-500 mt-1">${res.data?.message || 'Access denied or invalid order ID.'}</p>
                    <a href="/orders" class="inline-block mt-4 text-xs font-bold text-navy-900 hover:underline">View my orders &rarr;</a>
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
            pending: 'bg-amber-50 text-amber-800 border-amber-200',
            processing: 'bg-blue-50 text-blue-800 border-blue-200',
            out_for_delivery: 'bg-purple-50 text-purple-800 border-purple-200',
            shipped: 'bg-indigo-50 text-indigo-800 border-indigo-200',
            delivered: 'bg-emerald-50 text-emerald-800 border-emerald-200',
            completed: 'bg-emerald-50 text-emerald-800 border-emerald-200',
            cancelled: 'bg-rose-50 text-rose-800 border-rose-200',
        };
        const statusLabels = {
            pending: 'Pending Payment',
            processing: 'Confirmed / Processing',
            out_for_delivery: 'Out for Delivery',
            shipped: 'Shipped / In Transit',
            delivered: 'Delivered',
            completed: 'Completed',
            cancelled: 'Cancelled',
        };
        const badgeClass = statusColors[order.status] || 'bg-slate-100 text-slate-800 border-slate-200';
        const displayStatus = statusLabels[order.status] || order.status;
        const formattedDeliveryDate = order.expected_delivery_date
            ? new Date(order.expected_delivery_date).toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' })
            : null;

        container.innerHTML = `
            <!-- Receipt Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-black text-navy-950">Order #${order.id}</h1>
                        <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider ${badgeClass}">
                            ${displayStatus}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Placed on ${new Date(order.created_at).toLocaleString()}</p>
                    ${order.customer_email ? `
                        <p class="text-xs text-blue-600 mt-1 flex items-center gap-1.5 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Notifications sent to: <span class="font-bold">${order.customer_email}</span>
                        </p>
                    ` : ''}
                </div>

                ${order.status === 'pending' ? `
                    <button type="button" onclick="cancelOrder()"
                            class="py-2.5 px-4 border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Cancel Order
                    </button>
                ` : ''}
            </div>

            <!-- Expected Delivery Date Banner (if applicable) -->
            ${formattedDeliveryDate ? `
                <div class="p-5 bg-gradient-to-r from-navy-950 via-navy-900 to-navy-850 text-white rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md shadow-navy-950/10">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center flex-shrink-0 text-blue-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-blue-200">Estimated Delivery Arrival</div>
                            <div class="text-base font-black text-white">${formattedDeliveryDate}</div>
                        </div>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold text-navy-950 bg-white px-3 py-1.5 rounded-full self-start sm:self-auto shadow-xs">
                        Status: ${displayStatus}
                    </span>
                </div>
            ` : ''}

            <!-- Shipping & Payment Information Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-5 bg-slate-50/80 rounded-2xl border border-slate-100 space-y-1.5">
                    <span class="text-[11px] font-bold text-navy-950 uppercase tracking-wider block mb-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-navy-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Delivery Destination
                    </span>
                    <p class="font-bold text-sm text-slate-900">${addr.name || 'Recipient'}</p>
                    <p class="text-slate-600">${addr.address_line1 || ''}${addr.address_line2 ? ', ' + addr.address_line2 : ''}</p>
                    <p class="text-slate-600">${addr.city || ''}, ${addr.state || ''} ${addr.postal_code || ''}</p>
                    <p class="text-slate-600">${addr.country || ''}</p>
                    <p class="text-slate-400 pt-1 font-mono">Phone: ${addr.phone || 'N/A'}</p>
                    ${order.customer_email ? `<p class="text-blue-600 font-semibold pt-0.5">Email: ${order.customer_email}</p>` : ''}
                </div>

                <div class="p-5 bg-slate-50/80 rounded-2xl border border-slate-100 space-y-1.5">
                    <span class="text-[11px] font-bold text-navy-950 uppercase tracking-wider block mb-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-navy-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Billing & Payment
                    </span>
                    <p class="text-slate-700"><span class="font-bold text-slate-900">Method:</span> <span class="uppercase font-semibold">${payment.payment_method || 'N/A'}</span></p>
                    <p class="text-slate-700"><span class="font-bold text-slate-900">Payment Status:</span> <span class="capitalize font-semibold text-emerald-700">${payment.status || 'pending'}</span></p>
                    ${coupon ? `
                        <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl mt-2 text-emerald-800">
                            Promo Applied: <span class="font-mono font-bold">${coupon.code}</span> (${coupon.discount_percent}% off)
                        </div>
                    ` : '<p class="text-slate-400 pt-1">No promotional coupon used</p>'}
                </div>
            </div>

            <!-- Items Table -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold text-navy-950 uppercase tracking-wider">Purchased Product Lines</h3>
                <div class="divide-y divide-slate-100 border-t border-b border-slate-100">
                    ${(order.items || []).map(item => {
                        const p = item.product || {};
                        const lineTotal = (parseFloat(item.price) * item.quantity).toFixed(2);
                        return `
                            <div class="py-3.5 flex items-center justify-between text-xs sm:text-sm">
                                <div>
                                    <p class="font-bold text-navy-950">${p.name || 'Product'}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Unit Price: $${parseFloat(item.price).toFixed(2)} &times; ${item.quantity} qty</p>
                                </div>
                                <span class="font-black text-navy-950 text-sm">$${lineTotal}</span>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>

            <!-- Total Breakdown -->
            <div class="flex justify-end pt-2">
                <div class="w-72 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Standard Delivery:</span>
                        <span class="text-emerald-600 font-bold uppercase tracking-wider">Free Shipping</span>
                    </div>
                    <div class="border-t border-slate-200 pt-2.5 flex justify-between text-base font-black text-navy-950">
                        <span>Grand Total:</span>
                        <span class="text-navy-950 text-xl font-black">$${parseFloat(order.total_amount).toFixed(2)}</span>
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
            showAlert('order-detail-alert', 'Order cancelled successfully. Product stock restored. Redirecting...', 'success');
            setTimeout(() => window.location.href = '/orders', 1200);
        } else {
            showAlert('order-detail-alert', res.data?.message || 'Failed to cancel order.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', loadOrder);
</script>
@endpush
