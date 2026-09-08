@extends('admin.layouts.app')
@section('title', 'Edit: '.$product->name)
@section('page-title', 'Edit Product')

@section('content')
<div class="flex justify-between items-center mb-24">
    <div></div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline">← Back to Products</a>
</div>

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="grid-2">
    <!-- Left column -->
    <div>
        <div class="card mb-16">
            <div class="card-title mb-16">Product Details</div>

            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="{{ old('price', $product->price) }}" required>
                    @error('price')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" class="form-control" min="0" value="{{ old('stock', $product->stock) }}" required>
                    @error('stock')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label for="category_id" style="margin-bottom: 0;">Category *</label>
                        <a href="{{ route('admin.categories.create') }}" target="_blank" style="font-size: 0.75rem; color: var(--primary); text-decoration: underline;">+ Add New Category</a>
                    </div>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select category…</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="active"   {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="card mb-16">
            <div class="card-title mb-16">Current Images</div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px">
                @forelse($product->images as $img)
                <div style="position:relative">
                    <img src="{{ asset('storage/'.ltrim($img->image, '/')) }}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid {{ $img->is_primary ? 'var(--primary)' : 'var(--border)' }}">
                    @if($img->is_primary)
                        <span style="position:absolute;top:-6px;left:-6px;background:var(--primary);color:#fff;font-size:.6rem;padding:2px 5px;border-radius:4px">Primary</span>
                    @endif
                    <div style="display:flex;gap:4px;margin-top:4px">
                        @if(!$img->is_primary)
                        <form method="POST" action="{{ route('admin.products.images.primary', [$product, $img]) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-xs btn-outline" title="Set as primary">★</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('admin.products.images.delete', [$product, $img]) }}"
                              onsubmit="return confirm('Delete image?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger">✕</button>
                        </form>
                    </div>
                </div>
                @empty
                <p class="text-muted text-sm">No images uploaded yet.</p>
                @endforelse
            </div>

            <div class="form-group">
                <label>Add More Images</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            </div>
        </div>

        <!-- Inventory Log -->
        <div class="card">
            <div class="card-title mb-16">Recent Inventory History</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Type</th><th>Qty</th><th>Before→After</th><th>By</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        @forelse($product->inventoryLogs as $log)
                        <tr>
                            <td class="text-sm text-muted">{{ $log->created_at->format('M d, Y H:i') }}</td>
                            <td><span class="badge badge-blue">{{ str_replace('_', ' ', $log->type) }}</span></td>
                            <td><strong>{{ $log->quantity }}</strong></td>
                            <td class="text-sm">{{ $log->quantity_before }} → {{ $log->quantity_after }}</td>
                            <td class="text-sm">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="text-sm text-muted">{{ $log->notes ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-muted text-sm" style="text-align:center;padding:16px">No inventory history.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div>
        <div class="card">
            <div class="card-title mb-16">Publish</div>
            <button type="submit" class="btn btn-primary w-full mb-16">💾 Update Product</button>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                  onsubmit="return confirm('Permanently delete this product?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger w-full">🗑 Delete Product</button>
            </form>
        </div>
    </div>
</div>
</form>
@endsection
