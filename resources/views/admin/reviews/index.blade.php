@extends('admin.layouts.app')
@section('title', 'Reviews')
@section('page-title', 'Review Moderation')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Comment or customer…" value="{{ request('search') }}">
        <select name="status" class="form-control">
            <option value="">All Status</option>
            <option value="pending"  {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Customer</th><th>Product</th><th>Rating</th><th>Comment</th><th>Status</th><th>Date</th><th style="text-align:right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                <tr>
                    <td><strong>{{ $review->user?->name ?? '—' }}</strong></td>
                    <td class="text-sm">{{ $review->product?->name ?? '—' }}</td>
                    <td>⭐ {{ $review->rating }}/5</td>
                    <td class="text-sm text-muted" style="max-width:250px">{{ Str::limit($review->comment, 100) }}</td>
                    <td>
                        <span class="badge {{ match($review->status) {
                            'approved' => 'badge-green',
                            'rejected' => 'badge-red',
                            default => 'badge-yellow'
                        } }}">{{ ucfirst($review->status) }}</span>
                    </td>
                    <td class="text-sm text-muted">{{ $review->created_at->format('M d, Y') }}</td>
                    <td style="text-align:right">
                        <div class="flex gap-8" style="justify-content:flex-end">
                            @if($review->status !== 'approved')
                            <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success btn-xs">✅ Approve</button>
                            </form>
                            @endif
                            @if($review->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-outline btn-xs">🚫 Reject</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}"
                                  onsubmit="return confirm('Delete this review?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted)">No reviews found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $reviews->links() }}</div>
</div>
@endsection
