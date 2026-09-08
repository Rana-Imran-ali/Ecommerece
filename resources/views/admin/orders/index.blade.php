@extends('admin.layouts.app')
@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Customer name or email…" value="{{ request('search') }}">
        <select name="status" class="form-control">
            <option value="">All Status</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th style="text-align:right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><strong>#{{ $order->id }}</strong></td>
                    <td>
                        <div>{{ $order->user?->name ?? 'N/A' }}</div>
                        <div class="text-sm text-muted">{{ $order->user?->email }}</div>
                    </td>
                    <td>{{ $order->items->count() }}</td>
                    <td><strong>${{ number_format($order->total_amount, 2) }}</strong></td>
                    <td>
                        @php
                            $cls = match($order->status) {
                                'delivered' => 'badge-green',
                                'cancelled' => 'badge-red',
                                'processing' => 'badge-blue',
                                'shipped' => 'badge-purple',
                                default => 'badge-yellow'
                            };
                        @endphp
                        <span class="badge {{ $cls }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td class="text-sm text-muted">{{ $order->created_at->format('M d, Y') }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline btn-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted)">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $orders->links() }}</div>
</div>
@endsection
