@extends('admin.layouts.app')
@section('title', 'Add Coupon')
@section('page-title', 'Create Coupon')

@section('content')
<div style="max-width:520px">
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
            <div class="form-row">
                <div class="form-group">
                    <label>Discount % *</label>
                    <input type="number" name="discount_percent" class="form-control" step="0.01" min="0" max="100"
                           value="{{ old('discount_percent') }}" placeholder="e.g. 15" required>
                    @error('discount_percent')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Max Discount $ (Optional)</label>
                    <input type="number" name="max_discount" class="form-control" step="0.01" min="0"
                           value="{{ old('max_discount') }}" placeholder="e.g. 50.00">
                    @error('max_discount')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Min Order Amount $ (Optional)</label>
                <input type="number" name="min_order_amount" class="form-control" step="0.01" min="0"
                       value="{{ old('min_order_amount') }}" placeholder="e.g. 100.00">
                @error('min_order_amount')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
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
@endsection
