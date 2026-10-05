@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Executive Overview')

@push('styles')
<style>
    .revenue-chart { width: 100%; height: 210px; position: relative; }
    .chart-bar-wrap { display: flex; align-items: flex-end; gap: 12px; height: 180px; padding-top: 14px; }
    .chart-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .chart-bar { 
        width: 100%; 
        max-width: 44px;
        background: linear-gradient(180deg, #1d4ed8 0%, #0a1128 100%); 
        border-radius: 8px 8px 2px 2px; 
        min-height: 6px; 
        transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.2);
    }
    .chart-bar:hover { 
        transform: translateY(-3px) scaleY(1.02);
        background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
        box-shadow: 0 8px 18px rgba(29, 78, 216, 0.35);
    }
    .chart-label { font-size: .72rem; font-weight: 700; color: #64748b; }
    .chart-val { font-size: .72rem; font-weight: 800; color: #0a1128; writing-mode: horizontal-tb; }

    .stat-card-custom {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem 1.35rem;
        box-shadow: 0 4px 16px -2px rgba(10, 17, 40, 0.04);
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px -4px rgba(10, 17, 40, 0.08);
    }
    .stat-icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        margin-bottom: 0.25rem;
    }
</style>
@endpush

@section('content')
<!-- Stats Row 1 – Revenue & Orders -->
<div class="stats-grid">
    <div class="stat-card-custom">
        <div class="flex items-center justify-between">
            <div class="stat-icon-wrap" style="background:#eff6ff;color:#1d4ed8;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Delivered</span>
        </div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value" style="color:#0a1128">${{ number_format($totalRevenue, 2) }}</div>
        <div class="stat-sub">All time delivered orders</div>
    </div>

    <div class="stat-card-custom">
        <div class="flex items-center justify-between">
            <div class="stat-icon-wrap" style="background:#ecfdf5;color:#10b981;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ now()->format('M Y') }}</span>
        </div>
        <div class="stat-label">This Month</div>
        <div class="stat-value" style="color:#0a1128">${{ number_format($monthRevenue, 2) }}</div>
        <div class="stat-sub">Revenue in {{ now()->format('F Y') }}</div>
    </div>

    <div class="stat-card-custom">
        <div class="flex items-center justify-between">
            <div class="stat-icon-wrap" style="background:#f5f3ff;color:#7c3aed;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">{{ $pendingOrders }} pending</span>
        </div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value" style="color:#0a1128">{{ number_format($totalOrders) }}</div>
        <div class="stat-sub">{{ $pendingOrders }} pending · {{ $processingOrders }} processing</div>
    </div>

    <div class="stat-card-custom">
        <div class="flex items-center justify-between">
            <div class="stat-icon-wrap" style="background:#e0f2fe;color:#0284c7;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-cyan-600 bg-cyan-50 px-2 py-0.5 rounded-full">+{{ $newCustomers }} new</span>
        </div>
        <div class="stat-label">Total Customers</div>
        <div class="stat-value" style="color:#0a1128">{{ number_format($totalCustomers) }}</div>
        <div class="stat-sub">{{ $newCustomers }} new this month</div>
    </div>

    <div class="stat-card-custom">
        <div class="flex items-center justify-between">
            <div class="stat-icon-wrap" style="background:#f1f5f9;color:#0a1128;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full">{{ $activeProducts }} live</span>
        </div>
        <div class="stat-label">Catalog Products</div>
        <div class="stat-value" style="color:#0a1128">{{ $totalProducts }}</div>
        <div class="stat-sub">{{ $activeProducts }} active in store</div>
    </div>

    <div class="stat-card-custom" style="border-left: 3px solid #f59e0b">
        <div class="flex items-center justify-between">
            <div class="stat-icon-wrap" style="background:#fffbeb;color:#d97706;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">{{ $outOfStock }} out</span>
        </div>
        <div class="stat-label">Stock Warnings</div>
        <div class="stat-value" style="color:#d97706">{{ $lowStock }}</div>
        <div class="stat-sub">{{ $outOfStock }} completely out of stock</div>
    </div>
</div>

<div class="grid-2">
    <!-- Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Revenue Trajectory</div>
                <div style="font-size:0.75rem;color:#64748b;margin-top:2px">Monthly delivered revenue over the last 6 months</div>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
                <span style="width:8px;height:8px;border-radius:50%;background:#1d4ed8;display:inline-block"></span>
                <span style="font-size:0.75rem;font-weight:700;color:#0a1128">Revenue ($)</span>
            </div>
        </div>
        @php
            $maxRevenue = $revenueChart->max() ?: 1;
        @endphp
        <div class="chart-bar-wrap">
            @forelse($revenueChart as $month => $total)
                <div class="chart-bar-col">
                    <div class="chart-val">${{ number_format($total / 1000, 1) }}k</div>
                    <div class="chart-bar" style="height: {{ max(10, round(($total / $maxRevenue) * 135)) }}px" title="{{ $month }}: ${{ number_format($total, 2) }}"></div>
                    <div class="chart-label">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</div>
                </div>
            @empty
                <p class="text-muted text-sm" style="margin:auto">No revenue data yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Recent Transactions</div>
                <div style="font-size:0.75rem;color:#64748b;margin-top:2px">Latest store customer purchases</div>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm">View All Orders &rarr;</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Customer</th><th>Amount</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" style="color:#1d4ed8;font-weight:700">#{{ $order->id }}</a>
                        </td>
                        <td>
                            <div style="font-weight:600;color:#0a1128">{{ $order->user?->name ?? 'Guest / N/A' }}</div>
                        </td>
                        <td>
                            <span style="font-weight:700;color:#0a1128">${{ number_format($order->total_amount, 2) }}</span>
                        </td>
                        <td>
                            @php
                                $cls = match($order->status) {
                                    'delivered' => 'badge-green',
                                    'cancelled' => 'badge-red',
                                    'processing', 'shipped' => 'badge-blue',
                                    default => 'badge-yellow'
                                };
                            @endphp
                            <span class="badge {{ $cls }}">{{ ucfirst($order->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-muted text-sm" style="text-align:center;padding:24px">No orders recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-16">
    <div class="card-header">
        <div>
            <div class="card-title" style="color:#d97706;display:flex;align-items:center;gap:6px">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Inventory Alerts
            </div>
            <div style="font-size:0.75rem;color:#64748b;margin-top:2px">Products requiring restock or attention</div>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline btn-sm">Manage Inventory &rarr;</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Image</th><th>Product Name</th><th>Current Stock</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($lowStockProducts as $product)
                <tr>
                    <td style="width:50px">
                        @if($product->primaryImage)
                            <img src="{{ asset('storage/'.ltrim($product->primaryImage->image, '/')) }}" class="img-thumb" alt="" style="border-radius:8px">
                        @else
                            <div class="img-thumb" style="background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:18px;border-radius:8px">📦</div>
                        @endif
                    </td>
                    <td><strong style="color:#0a1128">{{ $product->name }}</strong></td>
                    <td>
                        <span style="color: {{ $product->stock === 0 ? 'var(--danger)' : 'var(--warning)' }};font-weight:800">
                            {{ $product->stock }} units
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $product->stock === 0 ? 'badge-red' : 'badge-yellow' }}">
                            {{ $product->stock === 0 ? 'Out of Stock' : 'Low Stock' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline btn-xs">Edit Stock</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted text-sm" style="text-align:center;padding:24px">✅ All products are well stocked.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
