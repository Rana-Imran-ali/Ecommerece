@extends('admin.layouts.app')
@section('title', 'Categories')
@section('page-title', 'Categories')

@section('content')
<div class="grid-2">
    <!-- Category List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">All Categories</div>
        </div>
        <form method="GET" class="filter-bar mb-16">
            <input type="text" name="search" class="form-control" placeholder="Search…" value="{{ request('search') }}">
            <button class="btn btn-primary btn-sm">Search</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Description</th><th>Products</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td class="text-muted text-sm">{{ Str::limit($category->description, 60) ?? '—' }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td style="text-align:right">
                            <div class="flex gap-8" style="justify-content:flex-end">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline btn-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                      onsubmit="return confirm('Delete category {{ addslashes($category->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-muted text-sm" style="text-align:center;padding:20px">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $categories->links() }}</div>
    </div>

    <!-- Add Category Form -->
    <div class="card">
        <div class="card-title mb-16">Add New Category</div>
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary w-full">＋ Add Category</button>
        </form>
    </div>
</div>
@endsection
