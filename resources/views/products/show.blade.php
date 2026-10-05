@extends('layouts.app')

@section('title', 'Product Details - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs font-bold text-slate-500 uppercase tracking-wider">
        <a href="{{ url('/products') }}" class="hover:text-blue-600 transition-colors inline-flex items-center gap-1.5">
            &larr; Back to Catalog
        </a>
    </div>

    <!-- Alert Container -->
    <div id="details-alert"></div>

    <!-- Product Details Card -->
    <div id="product-details-card" class="bg-white rounded-3xl border border-slate-200/90 overflow-hidden p-6 sm:p-10 shadow-sm">
        <!-- Skeleton rendered on load -->
    </div>

    <!-- Customer Reviews Card -->
    <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-10 space-y-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-5 gap-3">
            <div>
                <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Verified Feedback</span>
                <h2 class="text-xl sm:text-2xl font-extrabold text-navy-950 mt-0.5">Customer Reviews</h2>
                <p id="reviews-summary" class="text-xs text-slate-400 mt-1">Loading customer ratings...</p>
            </div>
            <button type="button" onclick="toggleReviewForm()"
                    class="px-4 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-xs font-bold rounded-xl transition-all shadow-2xs self-start sm:self-auto">
                + Write a Review
            </button>
        </div>

        <!-- Review Submission Form -->
        <form id="review-form" onsubmit="handleReviewSubmit(event)" class="hidden p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-4">
                <label class="text-xs font-bold text-navy-950 uppercase tracking-wider">Rating Score:</label>
                <select id="review-rating" required class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 focus:ring-blue-500 font-semibold cursor-pointer">
                    <option value="5">★★★★★ (5 Stars - Excellent)</option>
                    <option value="4">★★★★☆ (4 Stars - Good)</option>
                    <option value="3">★★★☆☆ (3 Stars - Average)</option>
                    <option value="2">★★☆☆☆ (2 Stars - Below Average)</option>
                    <option value="1">★☆☆☆☆ (1 Star - Poor)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-1.5">Your Experience &amp; Comments</label>
                <textarea id="review-comment" rows="3" required
                          class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all placeholder-slate-400"
                          placeholder="Tell other shoppers about build quality, sizing, delivery, or general impressions..."></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-1">
                <button type="button" onclick="toggleReviewForm(false)" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" id="submit-review-btn" class="px-5 py-2 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all">
                    Post Review
                </button>
            </div>
        </form>

        <!-- Reviews List -->
        <div id="reviews-list" class="space-y-4 divide-y divide-slate-100">
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
                    <div class="w-full h-80 bg-slate-100 rounded-2xl flex items-center justify-center text-xs text-slate-400">Loading product images...</div>
                    <div class="flex space-x-2">
                        <div class="w-16 h-16 bg-slate-100 rounded-xl"></div>
                        <div class="w-16 h-16 bg-slate-100 rounded-xl"></div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="h-4 bg-slate-100 rounded w-1/4"></div>
                    <div class="h-8 bg-slate-100 rounded w-3/4"></div>
                    <div class="h-6 bg-slate-100 rounded w-1/3"></div>
                    <div class="h-20 bg-slate-100 rounded w-full"></div>
                    <div class="h-10 bg-slate-100 rounded w-1/2"></div>
                </div>
            </div>
        `;
    }

    function renderReviewsSkeleton() {
        document.getElementById('reviews-list').innerHTML = `
            <div class="space-y-3 pt-2 animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-1/4"></div>
                <div class="h-3 bg-slate-100 rounded w-3/4"></div>
                <div class="h-3 bg-slate-100 rounded w-1/2"></div>
            </div>
        `;
    }

    let currentProduct = null;
    let selectedOptions = {};
    let currentVariant = null;

    function findMatchingVariant() {
        if (!currentProduct || !currentProduct.variants || !currentProduct.variants.length) {
            return null;
        }
        const selectedValueIds = Object.values(selectedOptions);
        if (selectedValueIds.length !== (currentProduct.options?.length || 0)) {
            return null;
        }
        return currentProduct.variants.find(v => {
            if (v.option_value_ids.length !== selectedValueIds.length) return false;
            return selectedValueIds.every(id => v.option_value_ids.includes(id));
        }) || null;
    }

    function selectOption(optionId, valueId) {
        selectedOptions[optionId] = valueId;
        renderOptionPills();
        updateVariantState();
    }

    function renderOptionPills() {
        if (!currentProduct?.options) return;
        currentProduct.options.forEach(opt => {
            opt.values.forEach(val => {
                const btn = document.getElementById(`opt-btn-${opt.id}-${val.id}`);
                if (!btn) return;
                const isSelected = selectedOptions[opt.id] === val.id;
                if (isSelected) {
                    btn.className = 'px-3.5 py-1.5 rounded-xl border-2 border-blue-600 bg-blue-50 text-blue-700 text-xs font-bold transition-all shadow-xs';
                } else {
                    btn.className = 'px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-all';
                }
            });
        });
    }

    function updateVariantState() {
        if (!currentProduct?.options || currentProduct.options.length === 0) {
            return;
        }

        currentVariant = findMatchingVariant();
        const priceEl = document.getElementById('product-display-price');
        const badgeEl = document.getElementById('product-stock-badge');
        const skuEl = document.getElementById('product-sku-display');
        const qtyInput = document.getElementById('quantity-input');
        const maxLabel = document.getElementById('quantity-max-label');
        const addBtn = document.getElementById('btn-add-detail');
        const whatsappLink = document.getElementById('product-whatsapp-link');

        if (currentVariant) {
            const price = parseFloat(currentVariant.effective_price).toFixed(2);
            if (priceEl) priceEl.textContent = `$${price}`;
            if (skuEl) skuEl.textContent = currentVariant.sku ? `SKU: ${currentVariant.sku}` : '';

            const inStock = currentVariant.stock > 0;
            if (badgeEl) {
                badgeEl.className = inStock
                    ? 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200'
                    : 'px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200';
                badgeEl.textContent = inStock ? `In Stock (${currentVariant.stock} units)` : 'Out of Stock';
            }

            if (qtyInput) {
                qtyInput.max = currentVariant.stock;
                if (parseInt(qtyInput.value) > currentVariant.stock) {
                    qtyInput.value = Math.max(1, currentVariant.stock);
                }
                if (maxLabel) maxLabel.textContent = `(Max: ${currentVariant.stock})`;
            }

            if (addBtn) {
                if (inStock) {
                    addBtn.disabled = false;
                    addBtn.className = 'flex-1 py-3 px-5 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-sm font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all cursor-pointer';
                    addBtn.textContent = 'Add to Cart';
                } else {
                    addBtn.disabled = true;
                    addBtn.className = 'flex-1 py-3 px-5 bg-slate-200 text-slate-400 text-sm font-bold rounded-xl cursor-not-allowed';
                    addBtn.textContent = 'Out of Stock';
                }
            }

            if (whatsappLink) {
                const text = `Hello! I would like to ask about: ${currentProduct.name} (${currentVariant.title}) - $${price}\n${window.location.href}`;
                whatsappLink.href = `https://wa.me/{{ preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', '18005550199')) }}?text=${encodeURIComponent(text)}`;
            }
        } else {
            if (badgeEl) {
                badgeEl.className = 'px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200';
                badgeEl.textContent = 'Combination Unavailable';
            }
            if (addBtn) {
                addBtn.disabled = true;
                addBtn.className = 'flex-1 py-3 px-5 bg-slate-200 text-slate-400 text-sm font-bold rounded-xl cursor-not-allowed';
                addBtn.textContent = 'Unavailable';
            }
        }
    }

    async function loadProductDetails() {
        const container = document.getElementById('product-details-card');

        const res = await apiFetch(`/api/products/${productId}`);

        if (!res.ok) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <p class="text-lg font-bold text-rose-600">Product not found</p>
                    <p class="text-sm text-slate-500 mt-1">${res.data?.message || 'Unable to load product.'}</p>
                    <a href="/products" class="inline-block mt-4 text-sm font-bold text-blue-600 hover:underline">Return to product list</a>
                </div>
            `;
            return;
        }

        const p = res.data?.data;
        if (!p) return;

        currentProduct = p;
        selectedOptions = {};
        currentVariant = null;

        const hasVariants = p.options && p.options.length > 0 && p.variants && p.variants.length > 0;

        if (hasVariants) {
            const defaultVariant = p.variants.find(v => v.stock > 0) || p.variants[0];
            if (defaultVariant) {
                currentVariant = defaultVariant;
                p.options.forEach(opt => {
                    const matchedVal = opt.values.find(val => defaultVariant.option_value_ids.includes(val.id));
                    if (matchedVal) {
                        selectedOptions[opt.id] = matchedVal.id;
                    } else if (opt.values[0]) {
                        selectedOptions[opt.id] = opt.values[0].id;
                    }
                });
            }
        }

        const initialPrice = currentVariant ? currentVariant.effective_price : p.price;
        const initialStock = currentVariant ? currentVariant.stock : p.stock;
        const inStock = initialStock > 0;
        const initialSku = currentVariant?.sku || '';

        const primaryImgObj = p.images?.find(i => i.is_primary) || p.images?.[0];
        const resolveImgUrl = (img) => {
            if (!img) return null;
            if (img.url && !img.url.startsWith('http://localhost/')) return img.url;
            if (img.image) return `/storage/${img.image.replace(/^\/+/, '')}`;
            return img.url || null;
        };
        const mainImage = resolveImgUrl(primaryImgObj);

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
                <div class="space-y-4">
                    <div class="w-full h-88 bg-slate-50 rounded-2xl flex items-center justify-center overflow-hidden border border-slate-200/90 shadow-inner">
                        ${mainImage 
                            ? `<img id="active-image" src="${mainImage}" alt="${p.name}" class="h-full w-full object-contain p-4">`
                            : `<span class="text-sm text-slate-400">No Image Available</span>`}
                    </div>

                    ${p.images?.length > 1 ? `
                        <div class="flex items-center space-x-2.5 overflow-x-auto pb-2">
                            ${p.images.map(img => {
                                 const url = resolveImgUrl(img);
                                 return `
                                     <button type="button" onclick="document.getElementById('active-image').src='${url}'" class="w-16 h-16 rounded-xl border border-slate-200 overflow-hidden shrink-0 hover:border-blue-600 focus:outline-none transition-all">
                                         <img src="${url}" class="w-full h-full object-cover">
                                     </button>
                                 `;
                            }).join('')}
                        </div>
                    ` : ''}
                </div>

                <div class="space-y-5">
                    <div class="flex items-center justify-between text-xs font-bold text-blue-600 uppercase tracking-wider">
                        <span>Category: ${p.category?.name || 'General Catalog'}</span>
                        <span id="product-sku-display" class="text-slate-400 font-mono font-normal">${initialSku ? `SKU: ${initialSku}` : ''}</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-navy-950 tracking-tight leading-tight">${p.name}</h1>

                    <div class="flex items-center space-x-3.5">
                        <span id="product-display-price" class="text-3xl font-extrabold text-navy-950">$${parseFloat(initialPrice).toFixed(2)}</span>
                        <span id="product-stock-badge" class="px-3 py-1 rounded-full text-xs font-bold border ${inStock ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-rose-100 text-rose-800 border-rose-200'}">
                            ${inStock ? `In Stock (${initialStock} units)` : 'Out of Stock'}
                        </span>
                    </div>

                    <div class="border-t border-b border-slate-100 py-4 text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                        ${p.description || 'No detailed description provided for this product.'}
                    </div>

                    <!-- Variant Options Selector -->
                    ${p.options && p.options.length > 0 ? `
                        <div class="space-y-3.5 py-2 border-b border-slate-100">
                            ${p.options.map(opt => `
                                <div>
                                    <label class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-2">${opt.name}:</label>
                                    <div class="flex flex-wrap gap-2">
                                        ${opt.values.map(val => `
                                            <button type="button" 
                                                    id="opt-btn-${opt.id}-${val.id}" 
                                                    onclick="selectOption(${opt.id}, ${val.id})" 
                                                    class="px-3.5 py-1.5 rounded-xl border text-xs font-semibold transition-all ${selectedOptions[opt.id] === val.id ? 'border-2 border-blue-600 bg-blue-50 text-blue-700 shadow-2xs font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'}">
                                                ${val.value}
                                            </button>
                                        `).join('')}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}

                    ${inStock ? `
                        <div class="space-y-4 pt-2">
                            <!-- Stepper Quantity Selector -->
                            <div class="flex items-center space-x-3.5">
                                <label for="quantity-input" class="text-xs font-bold uppercase tracking-wider text-navy-950">Quantity:</label>
                                <div class="inline-flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                    <button type="button" onclick="stepQty(-1, ${p.stock})" class="px-3.5 py-2 text-slate-700 hover:bg-slate-200 font-bold transition-colors">-</button>
                                    <input type="number" id="quantity-input" min="1" max="${p.stock}" value="1"
                                           class="w-14 py-2 text-center text-sm font-bold border-none outline-none text-navy-950 bg-transparent">
                                    <button type="button" onclick="stepQty(1, ${p.stock})" class="px-3.5 py-2 text-slate-700 hover:bg-slate-200 font-bold transition-colors">+</button>
                                </div>
                                <span id="quantity-max-label" class="text-xs font-semibold text-slate-400">(Max: ${p.stock})</span>
                            </div>

                            <div class="flex items-center space-x-3">
                                <button type="button" id="btn-add-detail" onclick="handleAddWithQuantity(${p.id}, ${p.stock})"
                                        class="flex-1 py-3 px-5 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-sm font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all cursor-pointer">
                                    Add to Cart
                                </button>
                                <button type="button" onclick="addToWishlist(${p.id})"
                                        class="py-3 px-4 border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-bold rounded-xl transition-all flex items-center space-x-1.5">
                                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                    <span>Wishlist</span>
                                </button>
                            </div>

                            <a id="product-whatsapp-link" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', '18005550199')) }}?text=${encodeURIComponent('Hello! I would like to ask about product: ' + p.name + ' ($' + parseFloat(initialPrice).toFixed(2) + ')\n' + window.location.href)}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="w-full py-3 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4 fill-[#25D366]" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 1.891.526 3.662 1.438 5.177L2 22l4.982-1.408A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.2c-1.637 0-3.16-.487-4.437-1.325l-.318-.21-2.96.837.854-2.883-.231-.334A8.16 8.16 0 013.8 12c0-4.521 3.679-8.2 8.2-8.2s8.2 3.679 8.2 8.2-3.679 8.2-8.2 8.2z"/>
                                </svg>
                                <span>Inquire on WhatsApp</span>
                            </a>
                        </div>
                    ` : `
                        <div class="pt-3 space-y-2.5">
                            <button type="button" onclick="addToWishlist(${p.id})"
                                    class="w-full py-3 px-4 border border-slate-200 hover:bg-slate-50 text-navy-950 text-xs font-bold rounded-xl transition-all">
                                Save to Wishlist
                            </button>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', '18005550199')) }}?text=${encodeURIComponent('Hello! I would like to inquire about restock for: ' + p.name + '\n' + window.location.href)}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="w-full py-3 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-2">
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
            listEl.innerHTML = '<p class="text-xs text-rose-500">Failed to load reviews.</p>';
            summaryEl.textContent = 'Unable to load ratings.';
            return;
        }

        const data = res.data?.data || {};
        const reviews = data.reviews || [];
        const avg = data.average_rating || 0;
        const total = data.total_reviews || 0;

        summaryEl.textContent = total > 0 ? `Average Rating: ★ ${avg} / 5 (${total} verified review(s))` : 'No reviews yet for this product.';

        if (reviews.length === 0) {
            listEl.innerHTML = '<p class="text-sm text-slate-400 py-3">Be the first to review this product!</p>';
            return;
        }

        const currentUser = getAuthUser();

        listEl.innerHTML = reviews.map(r => {
            const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
            const isOwner = currentUser && currentUser.id === r.user_id;

            return `
                <div class="pt-4 pb-3 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-navy-950">${r.user?.name || 'Verified Customer'}</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-amber-500 font-bold tracking-widest text-sm">${stars}</span>
                            ${isOwner ? `<button type="button" onclick="deleteReview(${r.id})" class="text-rose-500 font-bold hover:underline">Delete</button>` : ''}
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 leading-relaxed">${r.comment || ''}</p>
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

    function stepQty(delta, maxStock) {
        const input = document.getElementById('quantity-input');
        if (!input) return;
        const current = parseInt(input.value) || 1;
        const next = Math.max(1, Math.min(maxStock, current + delta));
        input.value = next;
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

        const btn = document.getElementById('btn-add-detail');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Adding...';
        }

        const payload = {
            product_id: productId,
            quantity: qty
        };
        if (currentVariant && currentVariant.id) {
            payload.product_variant_id = currentVariant.id;
        }

        const res = await apiFetch('/api/cart/items', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            showAlert('details-alert', `Added ${qty} unit(s) to your shopping cart!`, 'success');
            if (btn) {
                btn.textContent = '✓ Added to Cart';
                btn.className = 'flex-1 py-3 px-5 bg-emerald-600 text-white text-sm font-bold rounded-xl shadow-xs transition-colors';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = 'Add to Cart';
                    btn.className = 'flex-1 py-3 px-5 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-sm font-bold rounded-xl shadow-md shadow-blue-700/20 transition-all cursor-pointer';
                }, 1500);
            }
            fetchNavbarCounts(true);
        } else {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Add to Cart';
            }
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
