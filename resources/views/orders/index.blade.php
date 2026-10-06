@extends('layouts.app')

@section('title', 'Order History - ' . config('app.name', 'EStore'))
@section('meta_description', 'Track and review all purchases placed under your account.')

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <!-- Header Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-navy-975 via-navy-900 to-navy-950 p-8 sm:p-10 text-white shadow-xl shadow-navy-950/10">
        <div class="absolute inset-0 bg-radial-at-t from-blue-600/15 via-transparent to-transparent pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/10 backdrop-blur-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Account Purchases
                </span>
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white">Order History</h1>
                <p class="text-slate-300 text-sm">Track, inspect receipts, and manage all current and past orders.</p>
            </div>
            <a href="{{ url('/products') }}" 
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold text-navy-950 bg-white hover:bg-slate-100 shadow-md transition-all self-start md:self-auto">
                <svg class="w-4 h-4 text-navy-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Continue Shopping
            </a>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="orders-alert"></div>

    <!-- Orders List Container -->
    <div id="orders-container" class="space-y-4">
        <div class="py-16 text-center text-slate-500 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="inline-block animate-spin w-8 h-8 border-3 border-navy-900 border-t-transparent rounded-full mb-3"></div>
            <div class="text-sm font-semibold text-slate-700">Loading order history...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadOrders() {
        if (!isUserAuthenticated()) {
            window.location.href = '/login?redirect=/orders';
            return;
        }

        const container = document.getElementById('orders-container');
        const res = await apiFetch('/api/orders');

        if (!res.ok) {
            container.innerHTML = `
                <div class="p-8 text-center bg-white rounded-2xl border border-red-200 text-red-600 shadow-sm">
                    Failed to fetch orders: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        const orders = res.data?.data || [];

        if (orders.length === 0) {
            container.innerHTML = `
                <div class="p-16 text-center bg-white rounded-3xl border border-slate-200/80 space-y-4 shadow-sm">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-navy-50 flex items-center justify-center text-navy-800">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-navy-950">No orders placed yet</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">When you complete purchases, your tracking numbers, receipts, and order statuses will appear here.</p>
                    <a href="/products" class="inline-block px-5 py-2.5 bg-navy-950 hover:bg-navy-900 text-white text-xs font-bold rounded-xl shadow-md shadow-navy-950/20 transition-all">
                        Explore Catalog &rarr;
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = orders.map(order => {
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
            const dateStr = new Date(order.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            const expectedDeliveryStr = order.expected_delivery_date
                ? new Date(order.expected_delivery_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
                : null;

            return `
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 space-y-4 hover:border-navy-300 hover:shadow-lg hover:shadow-navy-950/5 transition-all duration-200 group">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 border-b border-slate-100 gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-black text-navy-950 text-base">Order #${order.id}</span>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border uppercase tracking-wider ${badgeClass}">
                                ${displayStatus}
                            </span>
                        </div>
                        <div class="text-xs text-slate-400 font-medium">
                            Placed on ${dateStr}
                        </div>
                    </div>

                    <!-- Items Quick Preview -->
                    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                        <div class="space-y-1.5 text-sm text-slate-600">
                            <p class="font-bold text-slate-900">${order.items?.length || 0} product line${(order.items?.length || 0) === 1 ? '' : 's'}</p>
                            <p class="text-xs text-slate-500 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Destination: <span class="font-semibold text-slate-700">${order.address?.city || 'Shipping Address'}</span>
                            </p>
                            ${expectedDeliveryStr ? `<p class="text-xs font-bold text-blue-600 flex items-center gap-1"><span>🚚 Expected: ${expectedDeliveryStr}</span></p>` : ''}
                        </div>
                        <div class="sm:text-right">
                            <span class="text-xs text-slate-400 font-medium block">Total Paid</span>
                            <span class="text-xl font-black text-navy-950">$${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400">Order verification completed</span>
                        <a href="/orders/${order.id}" class="inline-flex items-center gap-1 text-xs font-bold text-navy-950 group-hover:text-blue-600 transition-colors">
                            View Receipt & Details &rarr;
                        </a>
                    </div>
                </div>
            `;
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', loadOrders);
</script>
@endpush
