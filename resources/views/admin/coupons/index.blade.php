@extends('admin.layouts.app')
@section('title', 'Coupons')
@section('page-title', 'Coupons')

@section('content')
<div class="grid-2">
    <!-- List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">All Coupons</div>
        </div>
        <form method="GET" class="filter-bar mb-16">
            <input type="text" name="search" class="form-control" placeholder="Coupon code…" value="{{ request('search') }}">
            <select name="status" class="form-control">
                <option value="">All</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button class="btn btn-primary btn-sm">Filter</button>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Uses</th><th>Active</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                    <tr>
                        <td><strong>{{ $coupon->code }}</strong></td>
                        <td>{{ $coupon->discount_percent }}%{{ $coupon->max_discount ? ' (max $'.number_format($coupon->max_discount,2).')' : '' }}</td>
                        <td>{{ $coupon->min_order_amount ? '$'.number_format($coupon->min_order_amount,2) : '—' }}</td>
                        <td>{{ $coupon->usages_count }}</td>
                        <td>
                            <span class="badge {{ $coupon->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            <div class="flex gap-8" style="justify-content:flex-end">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-outline btn-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                      onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">No coupons found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $coupons->links() }}</div>
    </div>

    <!-- Create Form -->
    <div class="card" style="align-self:start">
        <div class="card-title mb-16">Add New Coupon</div>
        <form method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            <div class="form-group">
                <label>Code *</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="SUMMER20" required>
                @error('code')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Discount % *</label>
                    <input type="number" name="discount_percent" class="form-control" step="0.01" min="0" max="100" value="{{ old('discount_percent') }}" required>
                    @error('discount_percent')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Max Discount $</label>
                    <input type="number" name="max_discount" class="form-control" step="0.01" min="0" value="{{ old('max_discount') }}" placeholder="Optional">
                </div>
            </div>
            <div class="form-group">
                <label>Min Order Amount $</label>
                <input type="number" name="min_order_amount" class="form-control" step="0.01" min="0" value="{{ old('min_order_amount') }}" placeholder="Optional">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : 'checked' }}>
                <label for="is_active" style="margin:0">Active</label>
            </div>
            <button type="submit" class="btn btn-primary w-full">＋ Create Coupon</button>
        </form>
    </div>
</div>
@endsection
