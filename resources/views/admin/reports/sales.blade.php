@extends('admin.layouts.app')
@section('title', 'Sales Report')
@section('page-title', 'Sales Report')

@section('content')
<div class="card mb-24">
    <form method="GET" class="filter-bar">
        <div class="form-group" style="margin:0">
            <label style="margin-bottom:4px;font-size:.75rem">From</label>
            <input type="date" name="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="form-group" style="margin:0">
            <label style="margin-bottom:4px;font-size:.75rem">To</label>
            <input type="date" name="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
        </div>
        <button class="btn btn-primary" style="align-self:flex-end">Generate</button>
    </form>
</div>

<div class="stats-grid mb-24">
    <div class="stat-card">
        <div class="stat-label">Total Revenue (delivered)</div>
        <div class="stat-value">${{ number_format($totalRevenue, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Delivered Orders</div>
        <div class="stat-value">{{ $totalOrders }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg. Order Value</div>
        <div class="stat-value">${{ $totalOrders > 0 ? number_format($totalRevenue / $totalOrders, 2) : '0.00' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-title mb-16">Daily Breakdown</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Date</th><th>Orders</th><th>Revenue</th></tr>
            </thead>
            <tbody>
                @forelse($daily as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->date)->format('D, M d, Y') }}</td>
                    <td>{{ $row->orders }}</td>
                    <td><strong>${{ number_format($row->total, 2) }}</strong></td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center;padding:32px;color:var(--muted)">No sales data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
