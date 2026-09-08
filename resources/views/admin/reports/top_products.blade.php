@extends('admin.layouts.app')
@section('title', 'Top Products')
@section('page-title', 'Top Selling Products')

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

<div class="card">
    <div class="card-title mb-16">Top 20 Products by Revenue</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr>
            </thead>
            <tbody>
                @forelse($products as $i => $product)
                <tr>
                    <td>
                        @if($i === 0) 🥇
                        @elseif($i === 1) 🥈
                        @elseif($i === 2) 🥉
                        @else {{ $i + 1 }}
                        @endif
                    </td>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>{{ number_format($product->total_qty) }}</td>
                    <td><strong>${{ number_format($product->revenue, 2) }}</strong></td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--muted)">No sales data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
