@extends('admin.layouts.app')
@section('title', 'Edit Coupon')
@section('page-title', 'Edit Coupon')

@section('content')
<div style="max-width:560px">
    <div class="flex justify-between items-center mb-24">
        <div></div>
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline">← Back</a>
    </div>
    <div class="card">
        <div class="card-title mb-16">Edit: {{ $coupon->code }}</div>
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Code *</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $coupon->code) }}" required style="text-transform:uppercase">
                @error('code')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Discount Type *</label>
                <select name="discount_type" id="discount_type_edit" class="form-control" onchange="toggleDiscountType(this.value)">
                    <option value="percent" {{ old('discount_type', $coupon->discount_type) === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                    <option value="fixed" {{ old('discount_type', $coupon->discount_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                </select>
            </div>

            <div class="form-row" id="percent-group">
                <div class="form-group">
                    <label>Discount % *</label>
                    <input type="number" name="discount_percent" id="discount_percent" class="form-control" step="0.01" min="0" max="100"
                           value="{{ old('discount_percent', $coupon->discount_percent) }}">
                    @error('discount_percent')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Max Discount $</label>
                    <input type="number" name="max_discount" class="form-control" step="0.01" min="0"
                           value="{{ old('max_discount', $coupon->max_discount) }}" placeholder="Optional cap">
                </div>
            </div>

            <div class="form-group" id="fixed-group" style="display:none">
                <label>Discount Amount $ *</label>
                <input type="number" name="discount_amount" id="discount_amount" class="form-control" step="0.01" min="0"
                       value="{{ old('discount_amount', $coupon->discount_amount) }}" placeholder="e.g. 20.00">
                @error('discount_amount')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min Order Amount $</label>
                    <input type="number" name="min_order_amount" class="form-control" step="0.01" min="0"
                           value="{{ old('min_order_amount', $coupon->min_order_amount) }}">
                </div>
                <div class="form-group">
                    <label>Max Global Uses</label>
                    <input type="number" name="max_uses" class="form-control" min="1" step="1"
                           value="{{ old('max_uses', $coupon->max_uses) }}" placeholder="Unlimited if blank">
                    @error('max_uses')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Expiration Date & Time</label>
                <input type="datetime-local" name="expires_at" class="form-control"
                       value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}">
                @error('expires_at')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                <label for="is_active" style="margin:0">Active</label>
            </div>

            <div style="margin-bottom:12px;font-size:.85rem;color:var(--muted)">
                Used <strong>{{ $coupon->usages->count() }}</strong> time(s)
                @if($coupon->max_uses) (Max limit: {{ $coupon->max_uses }}) @endif
            </div>

            <button type="submit" class="btn btn-primary w-full">💾 Update Coupon</button>
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
    const select = document.getElementById('discount_type_edit');
    if (select) toggleDiscountType(select.value);
});
</script>
@endsection
