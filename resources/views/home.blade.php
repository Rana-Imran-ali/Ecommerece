@extends('layouts.app')

@section('title', 'API Testing Frontend - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-8">
    <!-- Welcome Hero Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 sm:p-8">
        <div class="max-w-3xl">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mb-3">
                Backend / API Testing Console
            </span>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
                E-Commerce API Testing Frontend
            </h1>
            <p class="mt-3 text-gray-600 leading-relaxed">
                A simple, clean interface to interactively test all Laravel backend endpoints: authentication, product catalog, search, stock validation, shopping cart, wishlist, address book, and user profiles.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ url('/products') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    Browse Products
                </a>
                <a href="{{ url('/categories') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    View Categories
                </a>
            </div>
        </div>
    </div>

    <!-- API System Status Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
            <h2 class="text-base font-semibold text-gray-900">API Connectivity & Health</h2>
            <button type="button" onclick="checkApiHealth()" class="text-xs text-indigo-600 hover:underline">
                Re-check
            </button>
        </div>
        <div id="health-status" class="flex items-center space-x-3 text-sm text-gray-500">
            <div class="animate-spin w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
            <span>Testing connection to <code class="bg-gray-100 px-1 py-0.5 rounded text-gray-700">/api/products</code>...</span>
        </div>
    </div>

    <!-- Quick Navigation Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Products -->
        <a href="{{ url('/products') }}" class="group block p-6 bg-white rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-sm transition-all">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
                <h3 class="text-base font-semibold text-gray-900">Products API</h3>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Filter by category, search by keywords, verify pricing and stock availability, and inspect images.
            </p>
        </a>

        <!-- Categories -->
        <a href="{{ url('/categories') }}" class="group block p-6 bg-white rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-sm transition-all">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </span>
                <h3 class="text-base font-semibold text-gray-900">Categories API</h3>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Explore product counts per category and view products assigned to each category.
            </p>
        </a>

        <!-- Cart -->
        <a href="{{ url('/cart') }}" class="group block p-6 bg-white rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-sm transition-all">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
                <h3 class="text-base font-semibold text-gray-900">Shopping Cart API</h3>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Test adding items, incrementing/decrementing quantities with real-time stock validation and subtotals.
            </p>
        </a>

        <!-- Wishlist -->
        <a href="{{ url('/wishlist') }}" class="group block p-6 bg-white rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-sm transition-all">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </span>
                <h3 class="text-base font-semibold text-gray-900">Wishlist API</h3>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Test item bookmarks, moving items into cart, and clearing user wishlist collections.
            </p>
        </a>

        <!-- Addresses -->
        <a href="{{ url('/addresses') }}" class="group block p-6 bg-white rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-sm transition-all">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
                <h3 class="text-base font-semibold text-gray-900">Address Book API</h3>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Create, edit, delete shipping addresses, and designate default delivery addresses.
            </p>
        </a>

        <!-- Profile & Auth -->
        <a href="{{ url('/profile') }}" class="group block p-6 bg-white rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-sm transition-all">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                <h3 class="text-base font-semibold text-gray-900">Profile & Auth API</h3>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Inspect authenticated session, update user email/name, and test password changes.
            </p>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function checkApiHealth() {
        const statusEl = document.getElementById('health-status');
        statusEl.innerHTML = `
            <div class="animate-spin w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
            <span>Checking API endpoint response...</span>
        `;

        const res = await apiFetch('/api/products');
        if (res.ok) {
            const count = res.data?.meta?.total ?? res.data?.data?.length ?? 0;
            statusEl.innerHTML = `
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                <span class="text-green-700 font-medium">Online & Healthy</span>
                <span class="text-gray-400">|</span>
                <span class="text-gray-600">${count} product(s) available in database</span>
            `;
        } else {
            statusEl.innerHTML = `
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                <span class="text-red-700 font-medium">API Error: ${res.data?.message || 'Server error'}</span>
            `;
        }
    }

    document.addEventListener('DOMContentLoaded', checkApiHealth);
</script>
@endpush
