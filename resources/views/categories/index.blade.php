@extends('layouts.app')

@section('title', 'Product Categories - ' . config('app.name', 'EStore'))
@section('meta_description', 'Explore all department categories and product collections.')

@section('content')
<div class="space-y-8">
    <!-- Header Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-navy-975 via-navy-900 to-navy-950 p-8 sm:p-10 text-white shadow-xl shadow-navy-950/10">
        <div class="absolute inset-0 bg-radial-at-t from-blue-600/15 via-transparent to-transparent pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/10 backdrop-blur-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Department Directory
                </span>
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white">Product Categories</h1>
                <p class="text-slate-300 text-sm">Browse curated collections, explore our catalog hierarchy, and jump straight into your favorite departments.</p>
            </div>
            <div>
                <button type="button" onclick="toggleAddCategoryModal(true)" 
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold text-navy-950 bg-white hover:bg-slate-100 shadow-md transition-all active:scale-[0.98]">
                    <svg class="w-4 h-4 text-navy-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    New Category
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="category-alert"></div>

    <!-- Categories Grid -->
    <div id="categories-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Skeleton cards rendered on load -->
    </div>
</div>

<!-- Modal: Add Category -->
<div id="category-modal" class="hidden fixed inset-0 bg-navy-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-navy-100 max-w-md w-full p-6 sm:p-8 space-y-5 shadow-2xl animate-[fadeIn_0.2s_ease-out]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-navy-50 flex items-center justify-center text-navy-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                </div>
                <h3 class="text-base font-bold text-navy-950">Create New Category</h3>
            </div>
            <button type="button" onclick="toggleAddCategoryModal(false)" class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors font-bold">&times;</button>
        </div>
        <form onsubmit="handleCreateCategory(event)" class="space-y-4">
            <div>
                <label for="new-cat-name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Category Name</label>
                <input type="text" id="new-cat-name" required
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-navy-600 focus:border-navy-600 outline-none transition-all placeholder:text-slate-400"
                       placeholder="e.g. Premium Footwear, Smart Tech">
            </div>
            <div class="flex justify-end items-center gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="toggleAddCategoryModal(false)"
                        class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="save-cat-btn"
                        class="px-5 py-2.5 bg-navy-950 hover:bg-navy-900 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-navy-950/20">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function renderCategorySkeletons(count = 8) {
        document.getElementById('categories-container').innerHTML = Array.from({ length: count }).map(() => `
            <div class="bg-white rounded-2xl border border-slate-100 p-6 flex flex-col justify-between shadow-xs animate-pulse">
                <div>
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-slate-100 rounded-xl"></div>
                        <div class="w-20 h-5 bg-slate-100 rounded-full"></div>
                    </div>
                    <div class="h-6 bg-slate-100 rounded w-2/3 mt-5"></div>
                    <div class="h-3 bg-slate-100 rounded w-1/4 mt-2"></div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <div class="h-9 bg-slate-100 rounded-xl w-full"></div>
                </div>
            </div>
        `).join('');
    }

    function toggleAddCategoryModal(show) {
        const modal = document.getElementById('category-modal');
        if (show) {
            modal.classList.remove('hidden');
            document.getElementById('new-cat-name').focus();
        } else {
            modal.classList.add('hidden');
            document.getElementById('new-cat-name').value = '';
        }
    }

    async function loadCategories(force = false) {
        const container = document.getElementById('categories-container');
        renderCategorySkeletons(4);

        const res = await apiFetch('/api/categories', { bypassCache: force });

        if (!res.ok) {
            container.innerHTML = `
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-red-200 text-red-600 shadow-sm">
                    Failed to load categories: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        const categories = res.data?.data || [];

        if (categories.length === 0) {
            container.innerHTML = `
                <div class="col-span-full p-16 text-center bg-white rounded-3xl border border-slate-200/80 text-slate-500 shadow-sm">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-navy-50 flex items-center justify-center text-navy-800">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <p class="text-base font-bold text-navy-950">No categories found</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Create your first store category using the "+ New Category" button above.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = categories.map(cat => {
            const count = cat.products_count ?? 0;
            return `
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 flex flex-col justify-between hover:border-navy-300 hover:shadow-lg hover:shadow-navy-950/5 transition-all duration-200 group">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="w-10 h-10 rounded-xl bg-navy-50 text-navy-900 flex items-center justify-center group-hover:bg-navy-950 group-hover:text-white transition-colors duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </span>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200/60">
                                ${count} product${count === 1 ? '' : 's'}
                            </span>
                        </div>
                        <h3 class="text-lg font-black text-navy-950 mt-4 group-hover:text-blue-600 transition-colors">${cat.name}</h3>
                        <p class="text-[11px] font-mono text-slate-400 mt-0.5">CAT #${cat.id}</p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <a href="/products?category_id=${cat.id}" 
                           class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-slate-50 hover:bg-navy-950 hover:text-white text-navy-950 text-xs font-bold rounded-xl border border-slate-200 hover:border-navy-950 transition-all duration-200">
                            Explore Catalog &rarr;
                        </a>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function handleCreateCategory(event) {
        event.preventDefault();
        const btn = document.getElementById('save-cat-btn');
        const name = document.getElementById('new-cat-name').value.trim();

        if (!name) return;

        btn.disabled = true;
        btn.textContent = 'Saving...';

        const res = await apiFetch('/api/categories', {
            method: 'POST',
            body: JSON.stringify({ name })
        });

        btn.disabled = false;
        btn.textContent = 'Save Category';

        if (res.ok) {
            toggleAddCategoryModal(false);
            showAlert('category-alert', `Category "${name}" created successfully!`, 'success');
            loadCategories(true);
        } else {
            showAlert('category-alert', res.data?.message || 'Failed to create category.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', () => loadCategories(false));
</script>
@endpush
