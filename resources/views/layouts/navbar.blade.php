<nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left: Brand / Logo & Desktop Links -->
            <div class="flex items-center space-x-8">
                <a href="{{ url('/') }}" class="flex items-center space-x-2 text-xl font-bold text-gray-900 tracking-tight hover:text-indigo-600 transition-colors">
                    <svg class="w-7 h-7 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>{{ config('app.name', 'EStore') }}</span>
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ url('/') }}"
                       class="text-sm font-medium transition-colors {{ request()->is('/') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                        Home
                    </a>
                    <a href="{{ url('/shop') }}"
                       class="text-sm font-medium transition-colors {{ request()->is('shop*') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                        Shop
                    </a>
                    <a href="{{ url('/products') }}"
                       class="text-sm font-medium transition-colors {{ request()->is('products*') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                        Products
                    </a>
                    <a href="{{ url('/categories') }}"
                       class="text-sm font-medium transition-colors {{ request()->is('categories*') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                        Categories
                    </a>
                    <a href="{{ url('/about') }}"
                       class="text-sm font-medium transition-colors {{ request()->is('about*') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                        About
                    </a>
                    <a href="{{ url('/contact') }}"
                       class="text-sm font-medium transition-colors {{ request()->is('contact*') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                        Contact
                    </a>
                </div>
            </div>

            <!-- Right: Search, Wishlist, Cart, User / Guest & Mobile Toggle -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <!-- Search -->
                <a href="{{ url('/search') }}"
                   class="p-2 text-gray-600 hover:text-indigo-600 transition-colors {{ request()->is('search*') ? 'text-indigo-600' : '' }}"
                   title="Search catalog">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </a>

                <!-- Wishlist -->
                <a href="{{ url('/wishlist') }}"
                   class="relative p-2 text-gray-600 hover:text-gray-900 transition-colors"
                   title="Wishlist">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span class="nav-wishlist-badge hidden absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                        0
                    </span>
                </a>

                <!-- Cart -->
                <a href="{{ url('/cart') }}"
                   class="relative p-2 text-gray-600 hover:text-gray-900 transition-colors"
                   title="Cart">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="nav-cart-badge hidden absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-indigo-600 rounded-full">
                        0
                    </span>
                </a>

                @php
                    $isAuth = auth()->check();
                    $user = $isAuth ? auth()->user() : null;
                    $avatarUrl = $user?->avatar
                        ? asset('storage/' . $user->avatar)
                        : ($user ? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=6366f1&color=ffffff&size=64&rounded=true&bold=true' : '');
                @endphp

                <!-- ── Guest Links (Desktop) ────────────────────────── -->
                <div class="nav-guest-only {{ $isAuth ? 'hidden' : 'hidden md:flex' }} items-center space-x-2 pl-3 border-l border-gray-200" id="nav-guest-area">
                    <a href="{{ url('/login') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors px-3 py-1.5 rounded-md hover:bg-gray-50">
                        Login
                    </a>
                    <a href="{{ url('/register') }}" class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md px-3.5 py-1.5 transition-colors shadow-sm">
                        Register
                    </a>
                </div>

                <!-- ── Authenticated User Flow (Desktop) ────────────── -->
                <div class="nav-auth-only {{ $isAuth ? 'hidden md:flex' : 'hidden' }} items-center space-x-2 pl-3 border-l border-gray-200" id="nav-auth-area">
                    <!-- Direct My Account / Profile Button: clicking opens customer dashboard -->
                    <a href="{{ url('/account') }}"
                       id="nav-account-btn"
                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 hover:text-indigo-800 border border-indigo-200 transition-all shadow-xs group"
                       title="Open My Account / Profile Dashboard">
                        <img src="{{ $avatarUrl }}"
                             alt="{{ $user?->name ?? 'User' }}"
                             class="nav-user-avatar w-6 h-6 rounded-full object-cover border border-indigo-300 group-hover:scale-105 transition-transform flex-shrink-0">
                        <span class="nav-account-text font-medium">My Account/Profile</span>
                    </a>

                    <!-- Account Quick-Access Dropdown Toggle -->
                    <div class="relative" id="account-dropdown-wrap">
                        <button type="button"
                                id="account-menu-btn"
                                class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-gray-100 rounded-lg transition-colors focus:outline-none"
                                aria-haspopup="true"
                                aria-expanded="false"
                                title="Account Quick Menu">
                            <svg class="w-4 h-4 transition-transform" id="account-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="account-dropdown"
                             class="hidden absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-xl py-1 z-50">
                            <div class="px-4 py-2.5 border-b border-gray-100">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Signed in as</div>
                                <div class="nav-user-name text-sm font-bold text-gray-800 truncate">{{ $user?->name ?? 'Customer' }}</div>
                            </div>
                            <a href="{{ url('/account') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Dashboard Overview
                            </a>
                            <a href="{{ url('/account') }}#profile" onclick="sessionStorage.setItem('account_tab','profile')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                My Profile
                            </a>
                            <a href="{{ url('/account') }}#orders" onclick="sessionStorage.setItem('account_tab','orders')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                My Orders & Status
                            </a>
                            <a href="{{ url('/account') }}#wishlist" onclick="sessionStorage.setItem('account_tab','wishlist')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                My Wishlist
                            </a>
                            <a href="{{ url('/account') }}#addresses" onclick="sessionStorage.setItem('account_tab','addresses')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Saved Addresses
                            </a>
                            <a href="{{ url('/account') }}#settings" onclick="sessionStorage.setItem('account_tab','settings')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Account Settings
                            </a>
                            <div class="border-t border-gray-100 mt-1 pt-1">
                                <button type="button" onclick="handleSignOut()" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Logout Button -->
                    <button type="button"
                            onclick="handleSignOut()"
                            class="text-xs font-medium text-gray-500 hover:text-red-600 px-2 py-1 transition-colors rounded hover:bg-red-50"
                            title="Sign out">
                        Logout
                    </button>
                </div>

                <!-- Mobile Menu Button -->
                <button type="button"
                        id="mobile-menu-toggle"
                        aria-controls="mobile-menu"
                        aria-expanded="false"
                        class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none">
                    <span class="sr-only">Open main menu</span>
                    <svg id="hamburger-icon" class="w-6 h-6 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 bg-white">
        <div class="px-4 pt-2 pb-3 space-y-1">
            <a href="{{ url('/') }}"
               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->is('/') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                Home
            </a>
            <a href="{{ url('/shop') }}"
               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->is('shop*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                Shop
            </a>
            <a href="{{ url('/products') }}"
               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->is('products*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                Products
            </a>
            <a href="{{ url('/search') }}"
               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->is('search*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                Search
            </a>
            <a href="{{ url('/categories') }}"
               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->is('categories*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                Categories
            </a>
            <a href="{{ url('/about') }}"
               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->is('about*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                About
            </a>
            <a href="{{ url('/contact') }}"
               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->is('contact*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                Contact
            </a>
            <a href="{{ url('/wishlist') }}"
               class="flex items-center justify-between px-3 py-2 rounded-md text-base font-medium {{ request()->is('wishlist*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                <span>Wishlist</span>
                <span class="nav-wishlist-badge hidden px-2 py-0.5 text-xs font-semibold text-white bg-red-500 rounded-full">0</span>
            </a>
            <a href="{{ url('/cart') }}"
               class="flex items-center justify-between px-3 py-2 rounded-md text-base font-medium {{ request()->is('cart*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                <span>Cart</span>
                <span class="nav-cart-badge hidden px-2 py-0.5 text-xs font-semibold text-white bg-indigo-600 rounded-full">0</span>
            </a>
        </div>

        <!-- Mobile User Account Section -->
        <div class="pt-3 pb-3 border-t border-gray-200 px-4">
            <!-- Authenticated Mobile Menu Flow -->
            <div class="nav-auth-only {{ $isAuth ? 'block' : 'hidden' }} space-y-2">
                <div class="flex items-center px-3 py-2 bg-gray-50 rounded-lg">
                    <img src="{{ $avatarUrl }}" alt="Avatar" class="nav-user-avatar w-10 h-10 rounded-full object-cover border-2 border-indigo-200 flex-shrink-0">
                    <div class="ml-3 min-w-0">
                        <div class="nav-user-name text-sm font-semibold text-gray-900 truncate">{{ $user?->name ?? 'Customer' }}</div>
                        <div class="nav-user-email text-xs text-gray-500 truncate">{{ $user?->email ?? '' }}</div>
                    </div>
                </div>
                <!-- Direct My Account / Profile Button -->
                <a href="{{ url('/account') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Account / Profile
                    </span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="{{ url('/account') }}#orders" onclick="sessionStorage.setItem('account_tab','orders')" class="px-3 py-2 rounded-md text-xs font-medium text-center text-gray-700 bg-gray-100 hover:bg-gray-200">
                        My Orders
                    </a>
                    <a href="{{ url('/account') }}#wishlist" onclick="sessionStorage.setItem('account_tab','wishlist')" class="px-3 py-2 rounded-md text-xs font-medium text-center text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Wishlist
                    </a>
                    <a href="{{ url('/account') }}#addresses" onclick="sessionStorage.setItem('account_tab','addresses')" class="px-3 py-2 rounded-md text-xs font-medium text-center text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Addresses
                    </a>
                    <a href="{{ url('/account') }}#settings" onclick="sessionStorage.setItem('account_tab','settings')" class="px-3 py-2 rounded-md text-xs font-medium text-center text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Settings
                    </a>
                </div>
                <button type="button" onclick="handleSignOut()" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </div>

            <!-- Guest Mobile Menu Flow -->
            <div class="nav-guest-only {{ $isAuth ? 'hidden' : 'block' }} space-y-2 pt-1">
                <a href="{{ url('/login') }}" class="block text-center w-full px-4 py-2 rounded-md text-sm font-semibold text-gray-700 border border-gray-300 hover:bg-gray-50">
                    Login
                </a>
                <a href="{{ url('/register') }}" class="block text-center w-full px-4 py-2 rounded-md text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">
                    Register
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── Mobile hamburger ── */
    const toggleBtn     = document.getElementById('mobile-menu-toggle');
    const mobileMenu    = document.getElementById('mobile-menu');
    const hamburgerIcon = document.getElementById('hamburger-icon');
    const closeIcon     = document.getElementById('close-icon');

    if (toggleBtn && mobileMenu) {
        toggleBtn.addEventListener('click', function () {
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            toggleBtn.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('hidden');
            hamburgerIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');
        });
    }

    /* ── Account dropdown ── */
    const accountBtn      = document.getElementById('account-menu-btn');
    const accountDropdown = document.getElementById('account-dropdown');
    const accountChevron  = document.getElementById('account-chevron');

    if (accountBtn && accountDropdown) {
        accountBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = !accountDropdown.classList.contains('hidden');
            accountDropdown.classList.toggle('hidden', isOpen);
            accountBtn.setAttribute('aria-expanded', !isOpen);
            if (accountChevron) accountChevron.style.transform = isOpen ? '' : 'rotate(180deg)';
        });

        // Close on outside click
        document.addEventListener('click', function () {
            accountDropdown.classList.add('hidden');
            accountBtn.setAttribute('aria-expanded', 'false');
            if (accountChevron) accountChevron.style.transform = '';
        });

        accountDropdown.addEventListener('click', e => e.stopPropagation());
    }

    // Run auth UI sync if api.js is loaded
    if (typeof updateNavAuthUI === 'function') {
        updateNavAuthUI();
    }
});
</script>
