@extends('admin.layouts.app')
@section('title', 'Edit Coupon')
@section('page-title', 'Edit Coupon')

@section('content')
<div style="max-width:520px">
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
                <input type="text" name="code" class="form-control" value="{{ old('code', $coupon->code) }}" required>
                @error('code')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Discount % *</label>
                    <input type="number" name="discount_percent" class="form-control" step="0.01" min="0" max="100"
                           value="{{ old('discount_percent', $coupon->discount_percent) }}" required>
                </div>
                <div class="form-group">
                    <label>Max Discount $</label>
                    <input type="number" name="max_discount" class="form-control" step="0.01" min="0"
                           value="{{ old('max_discount', $coupon->max_discount) }}">
                </div>
            </div>
            <div class="form-group">
                <label>Min Order Amount $</label>
                <input type="number" name="min_order_amount" class="form-control" step="0.01" min="0"
                       value="{{ old('min_order_amount', $coupon->min_order_amount) }}">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                <label for="is_active" style="margin:0">Active</label>
            </div>
            <div style="margin-bottom:8px;font-size:.8rem;color:var(--muted)">
                Used {{ $coupon->usages->count() }} time(s)
            </div>
            <button type="submit" class="btn btn-primary w-full">💾 Update Coupon</button>
        </form>
    </div>
</div>
@endsection
