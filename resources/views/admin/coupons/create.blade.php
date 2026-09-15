@extends('admin.layouts.app')
@section('title', 'Add Coupon')
@section('page-title', 'Create Coupon')

@section('content')
<div style="max-width:560px">
    <div class="flex justify-between items-center mb-24">
        <div></div>
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline">← Back</a>
    </div>
    <div class="card">
        <div class="card-title mb-16">Add New Coupon</div>
        <form method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            <div class="form-group">
                <label>Coupon Code *</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. SUMMER25" style="text-transform:uppercase" required>
                @error('code')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Discount Type *</label>
                <select name="discount_type" id="discount_type_create" class="form-control" onchange="toggleDiscountType(this.value)">
                    <option value="percent" {{ old('discount_type', 'percent') === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                    <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                </select>
            </div>

            <div class="form-row" id="percent-group">
                <div class="form-group">
                    <label>Discount % *</label>
                    <input type="number" name="discount_percent" id="discount_percent" class="form-control" step="0.01" min="0" max="100"
                           value="{{ old('discount_percent') }}" placeholder="e.g. 15">
                    @error('discount_percent')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Max Discount $ (Optional)</label>
                    <input type="number" name="max_discount" class="form-control" step="0.01" min="0"
                           value="{{ old('max_discount') }}" placeholder="e.g. 50.00">
                    @error('max_discount')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group" id="fixed-group" style="display:none">
                <label>Discount Amount $ *</label>
                <input type="number" name="discount_amount" id="discount_amount" class="form-control" step="0.01" min="0"
                       value="{{ old('discount_amount') }}" placeholder="e.g. 20.00">
                @error('discount_amount')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min Order Amount $ (Optional)</label>
                    <input type="number" name="min_order_amount" class="form-control" step="0.01" min="0"
                           value="{{ old('min_order_amount') }}" placeholder="e.g. 100.00">
                    @error('min_order_amount')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Max Global Uses (Optional)</label>
                    <input type="number" name="max_uses" class="form-control" min="1" step="1"
                           value="{{ old('max_uses') }}" placeholder="Unlimited if blank">
                    @error('max_uses')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Expiration Date & Time (Optional)</label>
                <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                @error('expires_at')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label for="is_active" style="margin:0">Active immediately</label>
            </div>

            <div class="flex gap-8">
                <button type="submit" class="btn btn-primary">Save Coupon</button>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDiscountType(type) {
    const percentGrp = document.getElementById('percent-group');
    const fixedGrp = document.getElementById('fixed-group');
    const percentInp = document.getElementById('discount_percent');
    const fixedInp = document.getElementById('discount_amount');

    if (type === 'fixed') {
        percentGrp.style.display = 'none';
        fixedGrp.style.display = 'block';
        if (percentInp) percentInp.removeAttribute('required');
        if (fixedInp) fixedInp.setAttribute('required', 'required');
    } else {
        percentGrp.style.display = 'flex';
        fixedGrp.style.display = 'none';
        if (fixedInp) fixedInp.removeAttribute('required');
        if (percentInp) percentInp.setAttribute('required', 'required');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('discount_type_create');
    if (select) toggleDiscountType(select.value);
});
</script>
@endsection
