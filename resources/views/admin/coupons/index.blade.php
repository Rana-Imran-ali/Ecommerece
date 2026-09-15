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
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Min Order</th>
                        <th>Uses</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                    <tr>
                        <td><strong>{{ $coupon->code }}</strong></td>
                        <td>
                            @if($coupon->discount_type === 'fixed')
                                <span class="badge badge-blue">${{ number_format($coupon->discount_amount, 2) }} Flat</span>
                            @else
                                <span>{{ $coupon->discount_percent }}%</span>
                                @if($coupon->max_discount)
                                    <small style="color:var(--muted);display:block">Max ${{ number_format($coupon->max_discount, 2) }}</small>
                                @endif
                            @endif
                        </td>
                        <td>{{ $coupon->min_order_amount ? '$'.number_format($coupon->min_order_amount,2) : '—' }}</td>
                        <td>
                            {{ $coupon->usages_count }}
                            @if($coupon->max_uses !== null)
                                / {{ $coupon->max_uses }}
                            @else
                                / ∞
                            @endif
                        </td>
                        <td>
                            @if($coupon->expires_at)
                                <span style="{{ $coupon->expires_at->isPast() ? 'color:var(--danger);font-weight:600' : '' }}">
                                    {{ $coupon->expires_at->format('M d, Y H:i') }}
                                </span>
                            @else
                                <span style="color:var(--muted)">Never</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $isExpired = $coupon->expires_at && $coupon->expires_at->isPast();
                                $isExhausted = $coupon->max_uses !== null && $coupon->usages_count >= $coupon->max_uses;
                            @endphp
                            @if(!$coupon->is_active)
                                <span class="badge badge-red">Inactive</span>
                            @elseif($isExpired)
                                <span class="badge badge-red">Expired</span>
                            @elseif($isExhausted)
                                <span class="badge badge-yellow">Exhausted</span>
                            @else
                                <span class="badge badge-green">Active</span>
                            @endif
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
                    <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted)">No coupons found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $coupons->links() }}</div>
    </div>

    <!-- Create Form -->
    <div class="card" style="align-self:start">
        <div class="card-title mb-16">Add New Coupon</div>
        <form method="POST" action="{{ route('admin.coupons.store') }}" id="create-coupon-form">
            @csrf
            <div class="form-group">
                <label>Code *</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="SUMMER20" style="text-transform:uppercase" required>
                @error('code')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Discount Type *</label>
                <select name="discount_type" id="discount_type" class="form-control" onchange="toggleDiscountFields(this.value)">
                    <option value="percent" {{ old('discount_type', 'percent') === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                    <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                </select>
            </div>

            <div class="form-row" id="percent-fields">
                <div class="form-group">
                    <label>Discount % *</label>
                    <input type="number" name="discount_percent" id="discount_percent" class="form-control" step="0.01" min="0" max="100" value="{{ old('discount_percent') }}">
                    @error('discount_percent')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Max Discount $</label>
                    <input type="number" name="max_discount" class="form-control" step="0.01" min="0" value="{{ old('max_discount') }}" placeholder="Optional cap">
                </div>
            </div>

            <div class="form-group" id="fixed-field" style="display:none">
                <label>Discount Amount $ *</label>
                <input type="number" name="discount_amount" id="discount_amount" class="form-control" step="0.01" min="0" value="{{ old('discount_amount') }}" placeholder="e.g. 15.00">
                @error('discount_amount')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min Order Amount $</label>
                    <input type="number" name="min_order_amount" class="form-control" step="0.01" min="0" value="{{ old('min_order_amount') }}" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label>Max Global Uses</label>
                    <input type="number" name="max_uses" class="form-control" min="1" step="1" value="{{ old('max_uses') }}" placeholder="Unlimited if blank">
                    @error('max_uses')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Expiration Date & Time</label>
                <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                @error('expires_at')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label for="is_active" style="margin:0">Active</label>
            </div>

            <button type="submit" class="btn btn-primary w-full">＋ Create Coupon</button>
        </form>
    </div>
</div>

<script>
function toggleDiscountFields(type) {
    const percentFields = document.getElementById('percent-fields');
    const fixedField = document.getElementById('fixed-field');
    const percentInput = document.getElementById('discount_percent');
    const fixedInput = document.getElementById('discount_amount');

    if (type === 'fixed') {
        percentFields.style.display = 'none';
        fixedField.style.display = 'block';
        if (percentInput) percentInput.removeAttribute('required');
        if (fixedInput) fixedInput.setAttribute('required', 'required');
    } else {
        percentFields.style.display = 'flex';
        fixedField.style.display = 'none';
        if (fixedInput) fixedInput.removeAttribute('required');
        if (percentInput) percentInput.setAttribute('required', 'required');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('discount_type');
    if (typeSelect) toggleDiscountFields(typeSelect.value);
});
</script>
@endsection
