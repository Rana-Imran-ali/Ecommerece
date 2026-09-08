@extends('admin.layouts.app')
@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
<div class="flex justify-between items-center mb-24">
    <div></div>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">＋ Add Product</a>
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Search products…" value="{{ request('search') }}">
        <select name="category_id" class="form-control">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <select name="stock_filter" class="form-control">
            <option value="">All Stock</option>
            <option value="low" {{ request('stock_filter') === 'low' ? 'selected' : '' }}>Low (≤10)</option>
            <option value="out" {{ request('stock_filter') === 'out' ? 'selected' : '' }}>Out of Stock</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Reviews</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        @if($product->primaryImage && $product->primaryImage->image)
                            <img src="{{ asset('storage/'.ltrim($product->primaryImage->image, '/')) }}" class="img-thumb" alt="{{ $product->name }}">
                        @else
                            <div class="img-thumb" style="background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:18px">📦</div>
                        @endif
                    </td>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td>${{ number_format($product->price, 2) }}</td>
                    <td>
                        <span style="font-weight:600;color:{{ $product->stock === 0 ? 'var(--danger)' : ($product->stock <= 10 ? 'var(--warning)' : 'inherit') }}">
                            {{ $product->stock }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $product->status === 'active' ? 'badge-green' : 'badge-gray' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </td>
                    <td>{{ $product->reviews_count }}</td>
                    <td style="text-align:right">
                        <div class="flex gap-8" style="justify-content:flex-end">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline btn-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">No products found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $products->links() }}
    </div>
</div>
@endsection
