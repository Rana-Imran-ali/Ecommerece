@extends('admin.layouts.app')
@section('title', $customer->name)
@section('page-title', 'Customer: '.$customer->name)

@section('content')
<div class="flex justify-between items-center mb-24">
    <div></div>
    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">← Back</a>
</div>

<div class="grid-2">
    <div>
        <!-- Profile Card -->
        <div class="card mb-16">
            <div class="card-title mb-16">Profile</div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <div><span class="text-muted text-sm">Name:</span> <strong>{{ $customer->name }}</strong></div>
                <div><span class="text-muted text-sm">Email:</span> {{ $customer->email }}</div>
                <div><span class="text-muted text-sm">Joined:</span> {{ $customer->created_at->format('M d, Y') }}</div>
                <div><span class="text-muted text-sm">Total Orders:</span> {{ $customer->orders->count() }}</div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="card mb-16">
            <div class="card-title mb-16">Addresses</div>
            @forelse($customer->addresses as $addr)
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:8px;font-size:.875rem;line-height:1.7">
                {{ $addr->street }}, {{ $addr->city }}, {{ $addr->state }} {{ $addr->postal_code }}, {{ $addr->country }}
                @if($addr->is_default) <span class="badge badge-green" style="margin-left:8px">Default</span>@endif
            </div>
            @empty
            <span class="text-muted text-sm">No addresses saved.</span>
            @endforelse
        </div>

        <!-- Recent Reviews -->
        <div class="card">
            <div class="card-title mb-16">Recent Reviews</div>
            @forelse($customer->reviews as $review)
            <div style="border-bottom:1px solid var(--border);padding-bottom:12px;margin-bottom:12px">
                <div class="flex justify-between">
                    <strong class="text-sm">{{ $review->product?->name }}</strong>
                    <span class="text-muted text-sm">⭐ {{ $review->rating }}/5</span>
                </div>
                <p class="text-sm text-muted">{{ $review->comment }}</p>
                <span class="badge {{ $review->status === 'approved' ? 'badge-green' : ($review->status === 'rejected' ? 'badge-red' : 'badge-yellow') }}">{{ ucfirst($review->status) }}</span>
            </div>
            @empty
            <span class="text-muted text-sm">No reviews yet.</span>
            @endforelse
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card" style="align-self:start">
        <div class="card-title mb-16">Recent Orders</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($customer->orders as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" style="color:var(--primary);font-weight:500">#{{ $order->id }}</a></td>
                        <td>{{ $order->items->count() }}</td>
                        <td>${{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <span class="badge {{ match($order->status) {
                                'delivered' => 'badge-green', 'cancelled' => 'badge-red',
                                'processing' => 'badge-blue', 'shipped' => 'badge-purple',
                                default => 'badge-yellow'
                            } }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="text-sm text-muted">{{ $order->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--muted)">No orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
