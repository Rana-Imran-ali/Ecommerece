@extends('admin.layouts.app')
@section('title', 'Customers')
@section('page-title', 'Customers')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Name or email…" value="{{ request('search') }}">
        <button class="btn btn-primary btn-sm">Search</button>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Orders</th><th>Joined</th><th style="text-align:right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->name }}</strong></td>
                    <td class="text-muted">{{ $customer->email }}</td>
                    <td>{{ $customer->orders_count }}</td>
                    <td class="text-sm text-muted">{{ $customer->created_at->format('M d, Y') }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline btn-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--muted)">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $customers->links() }}</div>
</div>
@endsection
