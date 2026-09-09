@extends('admin.layouts.app')
@section('title', 'Payments')
@section('page-title', 'Payments Management')

@section('content')
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon">💰</span>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">${{ number_format($totalRevenue, 2) }}</div>
        <div class="stat-sub">From completed payments</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">✅</span>
        <div class="stat-label">Completed</div>
        <div class="stat-value">{{ $completedCount }}</div>
        <div class="stat-sub">Successful transactions</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">⏳</span>
        <div class="stat-label">Pending</div>
        <div class="stat-value">{{ $pendingCount }}</div>
        <div class="stat-sub">Awaiting verification</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">⚠️</span>
        <div class="stat-label">Failed / Refunded</div>
        <div class="stat-value">{{ $failedCount }}</div>
        <div class="stat-sub">Needs attention</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Transaction Records</div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.payments.index') }}" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Search payment ID, order ID, or customer..." value="{{ request('search') }}">
        
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            @foreach($statuses as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>

        @if($methods->isNotEmpty())
        <select name="method" class="form-control">
            <option value="">All Payment Methods</option>
            @foreach($methods as $m)
                <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>{{ strtoupper($m) }}</option>
            @endforeach
        </select>
        @endif

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td><strong>#PAY-{{ $payment->id }}</strong></td>
                    <td>
                        <a href="{{ route('admin.orders.show', $payment->order_id) }}" style="color:var(--primary);text-decoration:none;font-weight:600">
                            #ORD-{{ $payment->order_id }}
                        </a>
                    </td>
                    <td>
                        <div>{{ $payment->order?->user?->name ?? 'Guest / Deleted' }}</div>
                        <div class="text-sm text-muted">{{ $payment->order?->user?->email ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-gray" style="font-family:monospace;letter-spacing:0.5px">
                            {{ strtoupper($payment->payment_method) }}
                        </span>
                    </td>
                    <td><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                    <td>
                        @php
                            $badgeClass = match($payment->status) {
                                'completed' => 'badge-green',
                                'failed' => 'badge-red',
                                'refunded' => 'badge-purple',
                                default => 'badge-yellow'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ ucfirst($payment->status) }}</span>
                    </td>
                    <td class="text-sm text-muted">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-outline btn-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:36px;color:var(--muted)">
                        No payment records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="pagination">
        {{ $payments->links() }}
    </div>
    @endif
</div>
@endsection
