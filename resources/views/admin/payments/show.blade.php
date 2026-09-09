@extends('admin.layouts.app')
@section('title', 'Payment Details #' . $payment->id)
@section('page-title', 'Payment Details: #PAY-' . $payment->id)

@section('content')
<div class="flex justify-between items-center mb-24">
    <div style="font-size:0.9rem;color:var(--muted)">
        Recorded on {{ $payment->created_at->format('F d, Y \a\t H:i:s') }}
    </div>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline">← Back to Payments</a>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="grid-2">
    <div>
        <!-- Payment Information -->
        <div class="card mb-16">
            <div class="card-header">
                <div class="card-title">Transaction Information</div>
                @php
                    $badgeClass = match($payment->status) {
                        'completed' => 'badge-green',
                        'failed' => 'badge-red',
                        'refunded' => 'badge-purple',
                        default => 'badge-yellow'
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ ucfirst($payment->status) }}</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;font-size:0.9rem">
                <div><span class="text-muted">Transaction ID:</span> <strong>#PAY-{{ $payment->id }}</strong></div>
                <div><span class="text-muted">Payment Method:</span> <span class="badge badge-gray">{{ strtoupper($payment->payment_method) }}</span></div>
                <div><span class="text-muted">Amount Charged:</span> <span style="font-size:1.25rem;font-weight:700;color:var(--text)">${{ number_format($payment->amount, 2) }}</span></div>
                <div><span class="text-muted">Created At:</span> {{ $payment->created_at->format('M d, Y H:i:s') }}</div>
                <div><span class="text-muted">Last Updated:</span> {{ $payment->updated_at->format('M d, Y H:i:s') }}</div>
            </div>

            <!-- Status Update Form -->
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
                <form action="{{ route('admin.payments.status', $payment) }}" method="POST" style="display:flex;gap:10px;align-items:flex-end">
                    @csrf
                    @method('PATCH')
                    <div style="flex:1">
                        <label for="status">Update Status</label>
                        <select name="status" id="status" class="form-control" style="width:100%">
                            <option value="pending" {{ $payment->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ $payment->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ $payment->status === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ $payment->status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>

        <!-- Customer Card -->
        <div class="card">
            <div class="card-title mb-16">Customer Details</div>
            @if($payment->order?->user)
            <div style="display:flex;flex-direction:column;gap:10px;font-size:0.9rem">
                <div><span class="text-muted">Name:</span> <strong>{{ $payment->order->user->name }}</strong></div>
                <div><span class="text-muted">Email:</span> {{ $payment->order->user->email }}</div>
                <div><span class="text-muted">Customer Since:</span> {{ $payment->order->user->created_at->format('M d, Y') }}</div>
                <div style="margin-top:8px">
                    <a href="{{ route('admin.customers.show', $payment->order->user) }}" class="btn btn-outline btn-xs">
                        View Customer Profile →
                    </a>
                </div>
            </div>
            @else
            <span class="text-muted text-sm">No linked customer profile found.</span>
            @endif
        </div>
    </div>

    <div>
        <!-- Associated Order -->
        <div class="card mb-16">
            <div class="card-header">
                <div class="card-title">Associated Order</div>
                @if($payment->order)
                <a href="{{ route('admin.orders.show', $payment->order) }}" class="btn btn-outline btn-xs">
                    View Full Order #{{ $payment->order_id }} →
                </a>
                @endif
            </div>

            @if($payment->order)
            <div style="display:flex;flex-direction:column;gap:10px;font-size:0.9rem;margin-bottom:16px">
                <div><span class="text-muted">Order ID:</span> <strong>#ORD-{{ $payment->order->id }}</strong></div>
                <div><span class="text-muted">Order Status:</span> <span class="badge badge-blue">{{ ucfirst($payment->order->status) }}</span></div>
                <div><span class="text-muted">Order Total:</span> <strong>${{ number_format($payment->order->total_amount, 2) }}</strong></div>
            </div>

            <!-- Items summary -->
            <div class="card-title mb-16" style="font-size:0.85rem">Order Items ({{ $payment->order->items->count() }})</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th style="text-align:right">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payment->order->items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? 'Product #'.$item->product_id }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td style="text-align:right">${{ number_format($item->unit_price, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted">No items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payment->order->address)
            <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border)">
                <div class="card-title" style="font-size:0.85rem;margin-bottom:6px">Shipping Address</div>
                <div style="font-size:0.85rem;color:var(--text);line-height:1.6">
                    {{ $payment->order->address->street }}, {{ $payment->order->address->city }}, {{ $payment->order->address->state }} {{ $payment->order->address->postal_code }}, {{ $payment->order->address->country }}
                </div>
            </div>
            @endif

            @else
            <span class="text-muted text-sm">No linked order found.</span>
            @endif
        </div>
    </div>
</div>
@endsection
