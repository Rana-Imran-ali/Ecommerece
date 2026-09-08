@extends('layouts.app')

@section('title', 'Categories - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Product Categories</h1>
            <p class="text-sm text-gray-500">Explore categories, product distribution, and manage taxonomy.</p>
        </div>
        <button type="button" onclick="toggleAddCategoryModal(true)" 
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm">
            + New Category
        </button>
    </div>

    <!-- Alert Container -->
    <div id="category-alert"></div>

    <!-- Categories Grid -->
    <div id="categories-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Skeleton cards rendered on load -->
    </div>
</div>

<!-- Modal: Add Category -->
<div id="category-modal" class="hidden fixed inset-0 bg-gray-600/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg border border-gray-200 max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-lg font-bold text-gray-900">Create New Category</h3>
            <button type="button" onclick="toggleAddCategoryModal(false)" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
        </div>
        <form onsubmit="handleCreateCategory(event)" class="space-y-4">
            <div>
                <label for="new-cat-name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                <input type="text" id="new-cat-name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                       placeholder="e.g. Footwear, Laptops">
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="toggleAddCategoryModal(false)"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="save-cat-btn"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-semibold">
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
            <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col justify-between animate-pulse">
                <div>
                    <div class="flex items-center justify-between">
                        <div class="w-9 h-9 bg-gray-200 rounded-md"></div>
                        <div class="w-20 h-5 bg-gray-200 rounded"></div>
                    </div>
                    <div class="h-6 bg-gray-200 rounded w-2/3 mt-4"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/4 mt-2"></div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <div class="h-8 bg-gray-200 rounded w-full"></div>
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
                <div class="col-span-full p-8 text-center bg-white rounded-lg border border-red-200 text-red-600">
                    Failed to load categories: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        const categories = res.data?.data || [];

        if (categories.length === 0) {
            container.innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-lg border border-gray-200 text-gray-500">
                    <p class="text-base font-semibold text-gray-800">No categories found</p>
                    <p class="text-sm mt-1">Create your first category using the "+ New Category" button above.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = categories.map(cat => {
            const count = cat.products_count ?? 0;
            return `
                <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col justify-between hover:border-indigo-400 hover:shadow-sm transition-all">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="p-2 bg-indigo-50 text-indigo-600 rounded-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </span>
                            <span class="text-xs font-semibold px-2 py-1 rounded bg-gray-100 text-gray-700">
                                ${count} product(s)
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mt-4">${cat.name}</h3>
                        <p class="text-xs text-gray-500 mt-1">ID: #${cat.id}</p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <a href="/products?category_id=${cat.id}" 
                           class="block text-center py-2 px-3 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 text-xs font-semibold rounded border border-gray-200 transition-colors">
                            View Products &rarr;
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
