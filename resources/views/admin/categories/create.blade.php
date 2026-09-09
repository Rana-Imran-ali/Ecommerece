@extends('admin.layouts.app')
@section('title', 'Add Category')
@section('page-title', 'Create Category')

@section('content')
<div style="max-width:520px">
    <div class="flex justify-between items-center mb-24">
        <div></div>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">← Back</a>
    </div>
    <div class="card">
        <div class="card-title mb-16">Add New Category</div>
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Electronics, Footwear" required>
                @error('name')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" placeholder="Short description of this category…">{{ old('description') }}</textarea>
                @error('description')<div class="form-error" style="color:var(--danger);font-size:0.8rem;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            <div class="flex gap-8">
                <button type="submit" class="btn btn-primary">Save Category</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
