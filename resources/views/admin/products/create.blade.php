@extends('admin.layouts.app')
@section('title', 'Add Product')
@section('page-title', 'Add Product')

@section('content')
<div class="flex justify-between items-center mb-24">
    <div></div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid-2">
    <div>
        <div class="card mb-16">
            <div class="card-title mb-16">Product Details</div>

            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                @error('description')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="{{ old('price') }}" required>
                    @error('price')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" class="form-control" min="0" value="{{ old('stock', 0) }}" required>
                    @error('stock')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label for="category_id" style="margin-bottom: 0;">Category *</label>
                        <a href="{{ route('admin.categories.create') }}" target="_blank" style="font-size: 0.75rem; color: var(--primary); text-decoration: underline;">+ Add New Category</a>
                    </div>
                    @if($categories->isEmpty())
                        <div class="alert alert-warning" style="padding: 8px 12px; margin-bottom: 8px; font-size: 0.8rem;">
                            ⚠️ No categories found. Please <a href="{{ route('admin.categories.create') }}" style="font-weight: 600; text-decoration: underline;">create a category first</a>.
                        </div>
                    @endif
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select category…</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="active"   {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title mb-16">Product Images</div>
            <div class="form-group">
                <label>Upload Images (first image = primary)</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                @error('images.*')<div class="form-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-title mb-16">Publish</div>
            <button type="submit" class="btn btn-primary w-full">💾 Save Product</button>
        </div>
    </div>
</div>
</form>
@endsection
