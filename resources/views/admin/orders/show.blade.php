@extends('admin.layouts.app')
@section('title', 'Order #'.$order->id)
@section('page-title', 'Order #'.$order->id)

@section('content')
<div class="flex justify-between items-center mb-24">
    <div></div>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">← Back to Orders</a>
</div>

<div class="grid-2">
    <!-- Left: Items & Info -->
    <div>
        <div class="card mb-16">
            <div class="card-title mb-16">Order Items</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Image</th><th>Product</th><th>Qty</th><th>Unit</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                @if($item->product?->primaryImage)
                                    <img src="{{ asset('storage/'.$item->product->primaryImage->image_url) }}" class="img-thumb" alt="">
                                @else
                                    <div class="img-thumb" style="background:#f1f5f9;display:flex;align-items:center;justify-content:center">📦</div>
                                @endif
                            </td>
                            <td>{{ $item->product?->name ?? 'Deleted Product' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->price, 2) }}</td>
                            <td>${{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="text-align:right;padding-top:12px;font-weight:700;font-size:1rem">
                Total: ${{ number_format($order->total_amount, 2) }}
            </div>
        </div>

        <!-- Payments -->
        @if($order->payments->count())
        <div class="card mb-16">
            <div class="card-title mb-16">Payments</div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Method</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach($order->payments as $payment)
                        <tr>
                            <td>{{ $payment->method ?? 'N/A' }}</td>
                            <td>${{ number_format($payment->amount, 2) }}</td>
                            <td><span class="badge {{ $payment->status === 'paid' ? 'badge-green' : 'badge-yellow' }}">{{ ucfirst($payment->status) }}</span></td>
                            <td class="text-sm text-muted">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Coupons -->
        @if($order->couponUsages->count())
        <div class="card">
            <div class="card-title mb-16">Coupons Applied</div>
            @foreach($order->couponUsages as $usage)
            <div class="flex justify-between mb-8">
                <span class="badge badge-purple">{{ $usage->coupon?->code }}</span>
                <span class="text-sm">-${{ number_format($usage->discount_amount ?? 0, 2) }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Right: Customer, Address, Status -->
    <div>
        <!-- Customer Info -->
        <div class="card mb-16">
            <div class="card-title mb-16">Customer</div>
            <div class="flex gap-8 mb-8">
                <strong>{{ $order->user?->name }}</strong>
            </div>
            <div class="text-sm text-muted mb-4">{{ $order->user?->email }}</div>
            <a href="{{ route('admin.customers.show', $order->user_id) }}" class="btn btn-outline btn-sm">View Profile</a>
        </div>

        <!-- Delivery Address -->
        <div class="card mb-16">
            <div class="card-title mb-16">Delivery Address</div>
            @if($order->address)
            <div class="text-sm" style="line-height:1.7">
                {{ $order->address->street }}<br>
                {{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }}<br>
                {{ $order->address->country }}
            </div>
            @else
            <span class="text-muted text-sm">No address on record.</span>
            @endif
        </div>

        <!-- Update Status -->
        <div class="card">
            <div class="card-title mb-16">Update Status</div>
            <div class="mb-8">
                Current:
                <span class="badge {{ match($order->status) {
                    'delivered' => 'badge-green', 'cancelled' => 'badge-red',
                    'processing' => 'badge-blue', 'shipped' => 'badge-purple',
                    default => 'badge-yellow'
                } }}">{{ ucfirst($order->status) }}</span>
            </div>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                @csrf @method('PATCH')
                <div class="form-group">
                    <select name="status" class="form-control" required>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-full">Update Status</button>
            </form>
            @if($order->status !== 'cancelled')
            <div class="alert alert-warning mt-16">
                ⚠️ Setting status to <strong>Cancelled</strong> will automatically restore stock to all products in this order.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
