@extends('layouts.app')

@section('title', 'My Orders - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Order History</h1>
            <p class="text-sm text-gray-500">Track and review all purchases placed under your account.</p>
        </div>
        <a href="{{ url('/products') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Continue Shopping
        </a>
    </div>

    <!-- Alert Container -->
    <div id="orders-alert"></div>

    <!-- Orders List Container -->
    <div id="orders-container" class="space-y-4">
        <div class="py-16 text-center text-gray-500 bg-white rounded-lg border border-gray-200">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
            <div>Loading order history...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadOrders() {
        if (!getAuthToken()) {
            window.location.href = '/login?redirect=/orders';
            return;
        }

        const container = document.getElementById('orders-container');
        const res = await apiFetch('/api/orders');

        if (!res.ok) {
            container.innerHTML = `
                <div class="p-8 text-center bg-white rounded-lg border border-red-200 text-red-600">
                    Failed to fetch orders: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        const orders = res.data?.data || [];

        if (orders.length === 0) {
            container.innerHTML = `
                <div class="p-16 text-center bg-white rounded-lg border border-gray-200 space-y-4">
                    <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-800">No orders placed yet</h3>
                    <p class="text-sm text-gray-500">When you place orders through checkout, they will appear here.</p>
                    <a href="/products" class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                        Start Shopping
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = orders.map(order => {
            const statusColors = {
                pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                completed: 'bg-green-100 text-green-800 border-green-200',
                cancelled: 'bg-red-100 text-red-800 border-red-200',
            };
            const badgeClass = statusColors[order.status] || 'bg-gray-100 text-gray-800 border-gray-200';
            const dateStr = new Date(order.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

            return `
                <div class="bg-white rounded-lg border border-gray-200 p-5 sm:p-6 space-y-4 hover:border-gray-300 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2">
                        <div class="flex items-center space-x-3">
                            <span class="font-bold text-gray-900 text-base">Order #${order.id}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border uppercase tracking-wider ${badgeClass}">
                                ${order.status}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Placed on ${dateStr}
                        </div>
                    </div>

                    <!-- Items Quick Preview -->
                    <div class="flex flex-wrap gap-4 items-center justify-between">
                        <div class="space-y-1 text-sm text-gray-600">
                            <p class="font-medium text-gray-800">${order.items?.length || 0} product(s) ordered</p>
                            <p class="text-xs text-gray-400">Delivered to: ${order.address?.city || 'Address'}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-400 block">Total Amount</span>
                            <span class="text-lg font-bold text-gray-900">$${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex justify-end">
                        <a href="/orders/${order.id}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
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
