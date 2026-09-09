@extends('admin.layouts.app')
@section('title', 'Manage User: ' . $user->name)
@section('page-title', 'User: ' . $user->name)

@section('content')
<div class="flex justify-between items-center mb-24">
    <div style="font-size:0.9rem;color:var(--muted)">
        Member since {{ $user->created_at->format('F d, Y') }}
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline">← Back to Users</a>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-error">
    {{ session('error') }}
</div>
@endif

<div class="grid-2">
    <div>
        <!-- Profile & Role Card -->
        <div class="card mb-16">
            <div class="card-header">
                <div class="card-title">Account Profile</div>
                @if($user->isAdmin())
                    <span class="badge badge-purple">Admin</span>
                @else
                    <span class="badge badge-blue">Customer</span>
                @endif
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;font-size:0.9rem">
                <div><span class="text-muted">User ID:</span> <strong>#USR-{{ $user->id }}</strong></div>
                <div><span class="text-muted">Full Name:</span> <strong>{{ $user->name }}</strong></div>
                <div><span class="text-muted">Email Address:</span> {{ $user->email }}</div>
                <div><span class="text-muted">Email Verified:</span> 
                    @if($user->email_verified_at)
                        <span class="badge badge-green">Verified ({{ $user->email_verified_at->format('M d, Y') }})</span>
                    @else
                        <span class="badge badge-yellow">Unverified</span>
                    @endif
                </div>
                <div><span class="text-muted">Registration Date:</span> {{ $user->created_at->format('M d, Y H:i') }}</div>
            </div>

            <!-- Role Update Form -->
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
                <form action="{{ route('admin.users.role', $user) }}" method="POST" style="display:flex;gap:10px;align-items:flex-end">
                    @csrf
                    @method('PATCH')
                    <div style="flex:1">
                        <label for="role">Assign Role</label>
                        <select name="role" id="role" class="form-control" style="width:100%" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                            <option value="customer" {{ !$user->isAdmin() ? 'selected' : '' }}>Customer (Regular User)</option>
                            <option value="admin" {{ $user->isAdmin() ? 'selected' : '' }}>Admin (Full Privileges)</option>
                        </select>
                    </div>
                    @if($user->id !== auth()->id())
                        <button type="submit" class="btn btn-primary">Save Role</button>
                    @else
                        <button type="button" class="btn btn-outline" disabled title="Cannot edit own role">Current Account</button>
                    @endif
                </form>
            </div>
        </div>

        <!-- Saved Addresses -->
        <div class="card mb-16">
            <div class="card-title mb-16">Saved Addresses ({{ $user->addresses->count() }})</div>
            @forelse($user->addresses as $addr)
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:8px;font-size:0.875rem;line-height:1.6">
                <div><strong>{{ $addr->recipient_name ?? $user->name }}</strong> @if($addr->phone) <span class="text-muted text-sm">({{ $addr->phone }})</span>@endif</div>
                <div class="text-muted">{{ $addr->street }}, {{ $addr->city }}, {{ $addr->state }} {{ $addr->postal_code }}, {{ $addr->country }}</div>
                @if($addr->is_default)
                    <span class="badge badge-green" style="margin-top:6px">Default Address</span>
                @endif
            </div>
            @empty
            <span class="text-muted text-sm">No addresses saved for this user.</span>
            @endforelse
        </div>
    </div>

    <div>
        <!-- Recent Orders -->
        <div class="card mb-16">
            <div class="card-header">
                <div class="card-title">Order History ({{ $user->orders->count() }})</div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($user->orders as $order)
                        <tr>
                            <td><strong>#{{ $order->id }}</strong></td>
                            <td>{{ $order->items->count() }} items</td>
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
                            <td style="text-align:right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline btn-xs">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">
                                No orders placed yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Product Reviews -->
        <div class="card">
            <div class="card-title mb-16">Product Reviews ({{ $user->reviews->count() }})</div>
            @forelse($user->reviews as $rev)
            <div style="border-bottom:1px solid var(--border);padding-bottom:12px;margin-bottom:12px">
                <div class="flex justify-between items-center mb-6">
                    <strong class="text-sm">{{ $rev->product?->name ?? 'Product #'.$rev->product_id }}</strong>
                    <span class="text-sm" style="color:#eab308">★ {{ $rev->rating }}/5</span>
                </div>
                <p class="text-sm text-muted" style="margin-bottom:6px">{{ $rev->comment }}</p>
                <span class="badge {{ $rev->status === 'approved' ? 'badge-green' : ($rev->status === 'rejected' ? 'badge-red' : 'badge-yellow') }}">
                    {{ ucfirst($rev->status) }}
                </span>
            </div>
            @empty
            <span class="text-muted text-sm">No reviews submitted.</span>
            @endforelse
        </div>
    </div>
</div>
@endsection
