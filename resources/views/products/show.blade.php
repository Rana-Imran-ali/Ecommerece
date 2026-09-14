@extends('layouts.app')

@section('title', 'Product Details - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-500">
        <a href="{{ url('/products') }}" class="hover:text-indigo-600 transition-colors">&larr; Back to Products</a>
    </div>

    <!-- Alert Container -->
    <div id="details-alert"></div>

    <!-- Product Details Card -->
    <div id="product-details-card" class="bg-white rounded-lg border border-gray-200 overflow-hidden p-6 sm:p-8">
        <!-- Skeleton rendered on load -->
    </div>

    <!-- Customer Reviews Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Customer Reviews</h2>
                <p id="reviews-summary" class="text-xs text-gray-500 mt-0.5">Loading reviews...</p>
            </div>
            <button type="button" onclick="toggleReviewForm()"
                    class="px-3.5 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-bold rounded-md transition-colors">
                + Write a Review
            </button>
        </div>

        <!-- Review Submission Form -->
        <form id="review-form" onsubmit="handleReviewSubmit(event)" class="hidden p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-3">
            <div class="flex items-center space-x-4">
                <label class="text-xs font-semibold text-gray-700 uppercase">Rating:</label>
                <select id="review-rating" required class="px-3 py-1.5 border border-gray-300 rounded text-sm bg-white outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="5">★★★★★ (5 Stars - Excellent)</option>
                    <option value="4">★★★★☆ (4 Stars - Good)</option>
                    <option value="3">★★★☆☆ (3 Stars - Average)</option>
                    <option value="2">★★☆☆☆ (2 Stars - Poor)</option>
                    <option value="1">★☆☆☆☆ (1 Star - Terrible)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Your Review</label>
                <textarea id="review-comment" rows="3" required
                          class="w-full px-3 py-2 border border-gray-300 rounded text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                          placeholder="Share your experience with this product..."></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="toggleReviewForm(false)" class="px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-200 rounded">
                    Cancel
                </button>
                <button type="submit" id="submit-review-btn" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded">
                    Post Review
                </button>
            </div>
        </form>

        <!-- Reviews List -->
        <div id="reviews-list" class="space-y-4 divide-y divide-gray-100">
            <!-- Skeleton rendered on load -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const productId = {{ $productId }};

    function renderProductSkeleton() {
        document.getElementById('product-details-card').innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start animate-pulse">
                <div class="space-y-4">
                    <div class="w-full h-80 bg-gray-200 rounded-lg"></div>
                    <div class="flex space-x-2">
                        <div class="w-16 h-16 bg-gray-200 rounded"></div>
                        <div class="w-16 h-16 bg-gray-200 rounded"></div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                    <div class="h-8 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-6 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-20 bg-gray-200 rounded w-full"></div>
                    <div class="h-10 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
        `;
    }

    function renderReviewsSkeleton() {
        document.getElementById('reviews-list').innerHTML = `
            <div class="space-y-3 animate-pulse pt-2">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 rounded w-1/2"></div>
            </div>
        `;
    }

    async function loadProductDetails() {
        const container = document.getElementById('product-details-card');

        const res = await apiFetch(`/api/products/${productId}`);

        if (!res.ok) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <p class="text-lg font-semibold text-red-600">Product not found</p>
                    <p class="text-sm text-gray-500 mt-1">${res.data?.message || 'Unable to load product.'}</p>
                    <a href="/products" class="inline-block mt-4 text-sm text-indigo-600 hover:underline">Return to product list</a>
                </div>
            `;
            return;
        }

        const p = res.data?.data;
        if (!p) return;

        const inStock = p.stock > 0;
        const primaryImgObj = p.images?.find(i => i.is_primary) || p.images?.[0];
        const resolveImgUrl = (img) => {
            if (!img) return null;
            if (img.url && !img.url.startsWith('http://localhost/')) return img.url;
            if (img.image) return `/storage/${img.image.replace(/^\/+/, '')}`;
            return img.url || null;
        };
        const mainImage = resolveImgUrl(primaryImgObj);

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                <div class="space-y-4">
                    <div class="w-full h-80 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border border-gray-100">
                        ${mainImage 
                            ? `<img id="active-image" src="${mainImage}" alt="${p.name}" class="h-full w-full object-contain">`
                            : `<span class="text-sm text-gray-400">No Image Available</span>`}
                    </div>

                    ${p.images?.length > 1 ? `
                        <div class="flex items-center space-x-2 overflow-x-auto pb-2">
                            ${p.images.map(img => {
                                const url = resolveImgUrl(img);
                                return `
                                    <button type="button" onclick="document.getElementById('active-image').src='${url}'" class="w-16 h-16 rounded border border-gray-200 overflow-hidden shrink-0 hover:border-indigo-600">
                                        <img src="${url}" class="w-full h-full object-cover">
                                    </button>
                                `;
                            }).join('')}
                        </div>
                    ` : ''}
                </div>

                <div class="space-y-4">
                    <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">
                        Category: ${p.category?.name || 'General'}
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">${p.name}</h1>

                    <div class="flex items-center space-x-3">
                        <span class="text-2xl font-bold text-gray-900">$${parseFloat(p.price).toFixed(2)}</span>
                        ${inStock 
                            ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">In Stock (${p.stock} units)</span>`
                            : `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Out of Stock</span>`}
                    </div>

                    <div class="border-t border-b border-gray-100 py-4 text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                        ${p.description || 'No detailed description provided for this product.'}
                    </div>

                    ${inStock ? `
                        <div class="space-y-4 pt-2">
                            <div class="flex items-center space-x-3">
                                <label for="quantity-input" class="text-sm font-medium text-gray-700">Quantity:</label>
                                <input type="number" id="quantity-input" min="1" max="${p.stock}" value="1"
                                       class="w-24 px-3 py-1.5 border border-gray-300 rounded-md text-sm text-center focus:ring-1 focus:ring-indigo-500 outline-none">
                                <span class="text-xs text-gray-500">(Max: ${p.stock})</span>
                            </div>

                            <div class="flex items-center space-x-3">
                                <button type="button" id="btn-add-detail" onclick="handleAddWithQuantity(${p.id}, ${p.stock})"
                                        class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-md shadow-sm transition-colors">
                                    Add to Cart
                                </button>
                                <button type="button" onclick="addToWishlist(${p.id})"
                                        class="py-2.5 px-4 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md transition-colors flex items-center space-x-1.5">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                    <span>Wishlist</span>
                                </button>
                            </div>

                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', '18005550199')) }}?text=${encodeURIComponent('Hello! I would like to ask about product: ' + p.name + ' ($' + parseFloat(p.price).toFixed(2) + ')\n' + window.location.href)}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-sm font-semibold rounded-md transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4 fill-[#25D366]" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                                </svg>
                                <span>Ask Questions on WhatsApp</span>
                            </a>
                        </div>
                    ` : `
                        <div class="pt-2 space-y-2">
                            <button type="button" onclick="addToWishlist(${p.id})"
                                    class="w-full py-2.5 px-4 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md transition-colors">
                                Save to Wishlist
                            </button>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', '18005550199')) }}?text=${encodeURIComponent('Hello! I would like to inquire about stock availability for: ' + p.name + '\n' + window.location.href)}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-sm font-semibold rounded-md transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4 fill-[#25D366]" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                                </svg>
                                <span>Inquire Restock on WhatsApp</span>
                            </a>
                        </div>
                    `}
                </div>
            </div>
        `;
    }

    async function loadReviews() {
        const listEl = document.getElementById('reviews-list');
        const summaryEl = document.getElementById('reviews-summary');

        const res = await apiFetch(`/api/products/${productId}/reviews`);

        if (!res.ok) {
            listEl.innerHTML = '<p class="text-xs text-red-500">Failed to load reviews.</p>';
            summaryEl.textContent = 'Unable to load ratings.';
            return;
        }

        const data = res.data?.data || {};
        const reviews = data.reviews || [];
        const avg = data.average_rating || 0;
        const total = data.total_reviews || 0;

        summaryEl.textContent = total > 0 ? `Average Rating: ★ ${avg} / 5 (${total} customer review(s))` : 'No reviews yet for this product.';

        if (reviews.length === 0) {
            listEl.innerHTML = '<p class="text-sm text-gray-400 py-3">Be the first to review this product!</p>';
            return;
        }

        const currentUser = getAuthUser();

        listEl.innerHTML = reviews.map(r => {
            const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
            const isOwner = currentUser && currentUser.id === r.user_id;

            return `
                <div class="pt-3 pb-2 space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-gray-800">${r.user?.name || 'Customer'}</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-amber-500 font-bold tracking-widest">${stars}</span>
                            ${isOwner ? `<button type="button" onclick="deleteReview(${r.id})" class="text-red-500 hover:underline">Delete</button>` : ''}
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">${r.comment || ''}</p>
                </div>
            `;
        }).join('');
    }

    function toggleReviewForm(show = null) {
        if (!getAuthToken()) {
            showAlert('details-alert', 'Please login to submit a product review.', 'warning');
            setTimeout(() => window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname), 1000);
            return;
        }

        const form = document.getElementById('review-form');
        if (show === null) {
            form.classList.toggle('hidden');
        } else if (show) {
            form.classList.remove('hidden');
        } else {
            form.classList.add('hidden');
        }
    }

    async function handleReviewSubmit(e) {
        e.preventDefault();
        const rating = parseInt(document.getElementById('review-rating').value);
        const comment = document.getElementById('review-comment').value.trim();
        const btn = document.getElementById('submit-review-btn');

        btn.disabled = true;
        btn.textContent = 'Posting...';

        const res = await apiFetch(`/api/products/${productId}/reviews`, {
            method: 'POST',
            body: JSON.stringify({ rating, comment })
        });

        btn.disabled = false;
        btn.textContent = 'Post Review';

        if (res.ok) {
            showAlert('details-alert', 'Review posted successfully!', 'success');
            document.getElementById('review-form').reset();
            toggleReviewForm(false);
            loadReviews();
        } else {
            showAlert('details-alert', res.data?.message || 'Failed to submit review.', 'danger');
        }
    }

    async function deleteReview(reviewId) {
        if (!confirm('Are you sure you want to delete your review?')) return;

        const res = await apiFetch(`/api/reviews/${reviewId}`, {
            method: 'DELETE'
        });

        if (res.ok) {
            showAlert('details-alert', 'Review deleted.', 'info');
            loadReviews();
        } else {
            showAlert('details-alert', res.data?.message || 'Failed to delete review.', 'danger');
        }
    }

    async function handleAddWithQuantity(productId, maxStock) {
        if (!getAuthToken()) {
            showAlert('details-alert', 'Please login to add items to your cart.', 'warning');
            setTimeout(() => window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname), 1200);
            return;
        }

        const qtyInput = document.getElementById('quantity-input');
        const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;

        if (qty < 1 || qty > maxStock) {
            showAlert('details-alert', `Please select a quantity between 1 and ${maxStock}.`, 'warning');
            return;
        }

        if (!getAuthToken()) {
            showAlert('details-alert', 'Please log in to add items to your shopping cart.', 'warning');
            setTimeout(() => window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname), 1200);
            return;
        }

        const btn = document.getElementById('btn-add-detail');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Adding...';
        }

        const res = await apiFetch('/api/cart/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: qty })
        });

        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Add to Cart';
        }

        if (res.ok) {
            showAlert('details-alert', `Added ${qty} unit(s) to your shopping cart!`, 'success');
            fetchNavbarCounts(true);
        } else {
            showAlert('details-alert', res.data?.message || 'Failed to add item.', 'danger');
        }
    }

    async function addToWishlist(productId) {
        if (!getAuthToken()) {
            showAlert('details-alert', 'Please login to add items to your wishlist.', 'warning');
            setTimeout(() => window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname), 1200);
            return;
        }

        const res = await apiFetch('/api/wishlist/items', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId })
        });

        if (res.ok) {
            showAlert('details-alert', 'Product saved to wishlist!', 'success');
            fetchNavbarCounts(true);
        } else {
            showAlert('details-alert', res.data?.message || 'Failed to save to wishlist.', 'danger');
        }
    }

    // Parallel fetch: Load product details and reviews simultaneously with zero waterfall
    document.addEventListener('DOMContentLoaded', () => {
        renderProductSkeleton();
        renderReviewsSkeleton();
        Promise.all([
            loadProductDetails(),
            loadReviews()
        ]);
    });
</script>
@endpush
