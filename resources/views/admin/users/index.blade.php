@extends('admin.layouts.app')
@section('title', 'Users')
@section('page-title', 'Users Management')

@section('content')
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon">👥</span>
        <div class="stat-label">Total Users</div>
        <div class="stat-value">{{ $totalUsers }}</div>
        <div class="stat-sub">Registered accounts</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🛡️</span>
        <div class="stat-label">Administrators</div>
        <div class="stat-value">{{ $adminCount }}</div>
        <div class="stat-sub">Full system access</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🛍️</span>
        <div class="stat-label">Customers</div>
        <div class="stat-value">{{ $customerCount }}</div>
        <div class="stat-sub">Shoppers & buyers</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">✨</span>
        <div class="stat-label">New This Month</div>
        <div class="stat-value">{{ $newThisMonth }}</div>
        <div class="stat-sub">Recent registrations</div>
    </div>
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

<div class="card">
    <div class="card-header">
        <div class="card-title">User Accounts</div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
        
        <select name="role" class="form-control">
            <option value="">All Roles</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>Customer</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Orders</th>
                    <th>Reviews</th>
                    <th>Addresses</th>
                    <th>Joined</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <strong>{{ $user->name }}</strong>
                                @if($user->id === auth()->id())
                                    <span class="badge badge-purple" style="margin-left:4px">You</span>
                                @endif
                                <div class="text-sm text-muted">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($user->isAdmin())
                            <span class="badge badge-purple">Admin</span>
                        @else
                            <span class="badge badge-blue">Customer</span>
                        @endif
                    </td>
                    <td>{{ $user->orders_count }}</td>
                    <td>{{ $user->reviews_count }}</td>
                    <td>{{ $user->addresses_count }}</td>
                    <td class="text-sm text-muted">{{ $user->created_at->format('M d, Y') }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline btn-xs">Manage</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:36px;color:var(--muted)">
                        No users found matching your criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="pagination">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
