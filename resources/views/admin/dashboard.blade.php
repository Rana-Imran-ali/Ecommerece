@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .revenue-chart { width: 100%; height: 200px; position: relative; }
    .chart-bar-wrap { display: flex; align-items: flex-end; gap: 8px; height: 180px; padding-top: 10px; }
    .chart-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .chart-bar { width: 100%; background: var(--primary); border-radius: 4px 4px 0 0; min-height: 4px; transition: opacity .15s; }
    .chart-bar:hover { opacity: .7; }
    .chart-label { font-size: .68rem; color: var(--muted); }
    .chart-val { font-size: .68rem; color: var(--muted); writing-mode: horizontal-tb; }
</style>
@endpush

@section('content')
<!-- Stats Row 1 – Revenue & Orders -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon">💰</span>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">${{ number_format($totalRevenue, 2) }}</div>
        <div class="stat-sub">All time (delivered orders)</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📅</span>
        <div class="stat-label">This Month</div>
        <div class="stat-value">${{ number_format($monthRevenue, 2) }}</div>
        <div class="stat-sub">Revenue in {{ now()->format('F Y') }}</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🛒</span>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value">{{ number_format($totalOrders) }}</div>
        <div class="stat-sub">{{ $pendingOrders }} pending · {{ $processingOrders }} processing</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">👥</span>
        <div class="stat-label">Customers</div>
        <div class="stat-value">{{ number_format($totalCustomers) }}</div>
        <div class="stat-sub">{{ $newCustomers }} new this month</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📦</span>
        <div class="stat-label">Products</div>
        <div class="stat-value">{{ $totalProducts }}</div>
        <div class="stat-sub">{{ $activeProducts }} active</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid var(--warning)">
        <span class="stat-icon">⚠️</span>
        <div class="stat-label">Low Stock</div>
        <div class="stat-value" style="color:var(--warning)">{{ $lowStock }}</div>
        <div class="stat-sub">{{ $outOfStock }} out of stock</div>
    </div>
</div>

<div class="grid-2">
    <!-- Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Revenue – Last 6 Months</div>
        </div>
        @php
            $maxRevenue = $revenueChart->max() ?: 1;
        @endphp
        <div class="chart-bar-wrap">
            @forelse($revenueChart as $month => $total)
                <div class="chart-bar-col">
                    <div class="chart-val">${{ number_format($total / 1000, 1) }}k</div>
                    <div class="chart-bar" style="height: {{ round(($total / $maxRevenue) * 140) }}px" title="{{ $month }}: ${{ number_format($total, 2) }}"></div>
                    <div class="chart-label">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</div>
                </div>
            @empty
                <p class="text-muted text-sm">No revenue data yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Orders</div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Customer</th><th>Amount</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" style="color:var(--primary);font-weight:500">#{{ $order->id }}</a></td>
                        <td>{{ $order->user?->name ?? 'N/A' }}</td>
                        <td>${{ number_format($order->total_amount, 2) }}</td>
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
                    <tr><td colspan="4" class="text-muted text-sm" style="text-align:center;padding:20px">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-16">
    <div class="card-header">
        <div class="card-title" style="color:var(--warning)">⚠️ Low / Out-of-Stock Products</div>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline btn-sm">Manage Inventory</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Image</th><th>Product</th><th>Stock</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($lowStockProducts as $product)
                <tr>
                    <td>
                        @if($product->primaryImage)
                            <img src="{{ asset('storage/'.$product->primaryImage->image_url) }}" class="img-thumb" alt="">
                        @else
                            <div class="img-thumb" style="background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:20px">📦</div>
                        @endif
                    </td>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>
                        <span style="color: {{ $product->stock === 0 ? 'var(--danger)' : 'var(--warning)' }};font-weight:600">
                            {{ $product->stock }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $product->stock === 0 ? 'badge-red' : 'badge-yellow' }}">
                            {{ $product->stock === 0 ? 'Out of Stock' : 'Low Stock' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline btn-xs">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted text-sm" style="text-align:center;padding:20px">✅ All products are well stocked.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
