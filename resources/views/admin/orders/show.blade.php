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
                                    <img src="{{ asset('storage/'.ltrim($item->product->primaryImage->image, '/')) }}" class="img-thumb" alt="">
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
            <div class="card-title mb-16">Customer & Notifications</div>
            <div class="flex gap-8 mb-8">
                <strong>{{ $order->user?->name ?? 'Guest / Customer' }}</strong>
            </div>
            <div class="text-sm text-muted mb-4">Account: {{ $order->user?->email ?? 'N/A' }}</div>
            <div class="text-sm mb-8" style="background:#f8fafc;padding:8px 10px;border-radius:6px;border:1px solid #e2e8f0;">
                <span class="text-xs text-muted block font-semibold uppercase">Notification Recipient Email</span>
                <strong style="color:#4f46e5;">{{ $order->customer_email ?: ($order->user?->email ?? 'None') }}</strong>
            </div>
            @if($order->user_id)
            <a href="{{ route('admin.customers.show', $order->user_id) }}" class="btn btn-outline btn-sm">View Profile</a>
            @endif
        </div>

        <!-- Delivery Address -->
        <div class="card mb-16">
            <div class="card-title mb-16">Delivery Details</div>
            @if($order->address)
            <div class="text-sm" style="line-height:1.7">
                <strong>{{ $order->address->name }}</strong><br>
                {{ $order->address->address_line1 }}{{ $order->address->address_line2 ? ', ' . $order->address->address_line2 : '' }}<br>
                {{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }}<br>
                {{ $order->address->country }}
                @if($order->address->phone)<br><span class="text-muted">Phone: {{ $order->address->phone }}</span>@endif
            </div>
            @else
            <span class="text-muted text-sm">No address on record.</span>
            @endif

            @if($order->expected_delivery_date)
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:0.875rem;">
                <span class="text-muted text-xs uppercase block font-semibold">Expected Delivery</span>
                <strong style="color:#0f172a;">{{ $order->expected_delivery_formatted }}</strong>
            </div>
            @endif
        </div>

        <!-- Update Status (Database Only) -->
        <div class="card mb-16">
            <div class="card-title mb-8">Update Order Status</div>
            <p class="text-xs text-muted mb-12">
                Updates the order status in the database only. <strong>Does not send an email</strong> to the customer.
            </p>
            <div class="mb-12">
                <span class="text-xs text-muted font-semibold uppercase block mb-4">Current Status:</span>
                <span class="badge {{ match($order->status) {
                    'delivered' => 'badge-green', 'cancelled' => 'badge-red',
                    'processing' => 'badge-blue', 'out_for_delivery', 'shipped' => 'badge-purple',
                    default => 'badge-yellow'
                } }}" style="font-size:0.8rem;padding:4px 12px;">{{ match($order->status) {
                    'processing' => 'Confirmed & Processing',
                    'out_for_delivery' => 'Out for Delivery',
                    'shipped' => 'Out for Delivery (Shipped)',
                    default => ucfirst($order->status)
                } }}</span>
            </div>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                @csrf @method('PATCH')
                <div class="form-group mb-12">
                    <label class="form-label" style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:700;display:block;margin-bottom:4px;">
                        New Status
                    </label>
                    <select name="status" class="form-control" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>
                                {{ match($s) {
                                    'pending' => 'Pending Confirmation',
                                    'processing' => 'Confirmed & Processing',
                                    'out_for_delivery' => 'Out for Delivery',
                                    'shipped' => 'Shipped / En Route',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                    default => ucfirst($s)
                                } }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-outline w-full" style="width:100%;justify-content:center;font-weight:600;">
                    💾 Save Status (No Email Sent)
                </button>
            </form>
            @if($order->status !== 'cancelled')
            <div class="alert alert-warning mt-12" style="font-size:0.75rem;padding:8px 10px;">
                ⚠️ Setting status to <strong>Cancelled</strong> automatically restores stock to inventory.
            </div>
            @endif
        </div>

        <!-- Customer Email Controls (Manual Actions) -->
        <div class="card" style="border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            <div class="card-title mb-8 flex items-center justify-between">
                <span>📧 Customer Email Controls</span>
                <span class="badge badge-purple" style="font-size:0.7rem;">Manual Trigger</span>
            </div>
            <p class="text-xs text-muted mb-12">
                Use the actions below to manually trigger transactional emails to the customer.
            </p>

            <div class="text-xs mb-16" style="background:#f8fafc;padding:8px 10px;border-radius:6px;border:1px solid #e2e8f0;">
                <span class="text-muted block font-semibold">Recipient Email:</span>
                <strong style="color:#4f46e5;font-size:0.85rem;">{{ $order->recipient_email ?: 'No email registered' }}</strong>
            </div>

            <!-- Action 1: Approval Email -->
            <div style="border-top:1px solid #f1f5f9;padding-top:14px;margin-bottom:16px;">
                <div style="font-size:0.82rem;font-weight:600;margin-bottom:4px;color:#0f172a;">
                    1. Send Approval / Confirmation Email
                </div>
                <p class="text-xs text-muted mb-8">
                    Sends a formal confirmation email letting the customer know their order is approved and being processed.
                </p>
                <form method="POST" action="{{ route('admin.orders.notify.approval', $order) }}" onsubmit="return confirm('Send order approval email to {{ $order->recipient_email }}?');">
                    @csrf
                    <button type="submit" class="btn btn-success w-full" style="width:100%;justify-content:center;font-weight:600;" {{ $order->status === 'cancelled' ? 'disabled' : '' }}>
                        ✉️ Send Approval Email
                    </button>
                    @if($order->status === 'cancelled')
                        <span class="text-xs text-danger block mt-4 text-center">Cannot send approval for a cancelled order.</span>
                    @endif
                </form>
            </div>

            <!-- Action 2: Delivery Date Email -->
            <div style="border-top:1px solid #f1f5f9;padding-top:14px;">
                <div style="font-size:0.82rem;font-weight:600;margin-bottom:4px;color:#0f172a;">
                    2. Send Expected Delivery Date Email
                </div>
                <p class="text-xs text-muted mb-8">
                    Schedules or updates the delivery date and sends an email notification to the customer.
                </p>
                <form method="POST" action="{{ route('admin.orders.notify.delivery-date', $order) }}" onsubmit="return confirm('Send delivery date email to {{ $order->recipient_email }}?');">
                    @csrf
                    <div class="form-group mb-10">
                        <label class="form-label" style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:700;display:block;margin-bottom:4px;">
                            Expected Delivery Date
                        </label>
                        <input type="date" name="expected_delivery_date" class="form-control" required min="{{ date('Y-m-d') }}"
                               value="{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('Y-m-d') : '' }}"
                               style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                    </div>
                    <button type="submit" class="btn btn-primary w-full" style="width:100%;justify-content:center;font-weight:600;">
                        📦 Send Delivery Date Email
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
