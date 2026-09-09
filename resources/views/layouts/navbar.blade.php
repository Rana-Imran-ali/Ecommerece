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

                <!-- Auth Area (Desktop) -->
                @auth
                    <div class="nav-auth-only flex items-center space-x-3 pl-3 border-l border-gray-200">
                        <a href="{{ url('/orders') }}" class="text-sm text-gray-600 hover:text-indigo-600 transition-colors" title="My Orders">
                            Orders
                        </a>
                        <a href="{{ url('/addresses') }}" class="text-sm text-gray-600 hover:text-indigo-600 transition-colors" title="My Addresses">
                            Addresses
                        </a>
                        <a href="{{ url('/profile') }}" class="flex items-center space-x-2 text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors">
                            <span class="nav-user-initial w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-semibold flex items-center justify-center border border-indigo-200">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="nav-user-name max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                        </a>
                        <button type="button" onclick="handleSignOut()" class="text-sm font-medium text-gray-500 hover:text-red-600 transition-colors px-2 py-1">
                            Logout
                        </button>
                    </div>
                @else
                    <div class="nav-auth-only hidden md:flex items-center space-x-3 pl-3 border-l border-gray-200">
                        <a href="{{ url('/orders') }}" class="text-sm text-gray-600 hover:text-indigo-600 transition-colors" title="My Orders">
                            Orders
                        </a>
                        <a href="{{ url('/addresses') }}" class="text-sm text-gray-600 hover:text-indigo-600 transition-colors" title="My Addresses">
                            Addresses
                        </a>
                        <a href="{{ url('/profile') }}" class="flex items-center space-x-2 text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors">
                            <span class="nav-user-initial w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-semibold flex items-center justify-center border border-indigo-200">
                                U
                            </span>
                            <span class="nav-user-name max-w-[120px] truncate">User</span>
                        </a>
                        <button type="button" onclick="handleSignOut()" class="text-sm font-medium text-gray-500 hover:text-red-600 transition-colors px-2 py-1">
                            Sign Out
                        </button>
                    </div>

                    <div class="nav-guest-only flex items-center space-x-2 pl-3 border-l border-gray-200">
                        <a href="{{ url('/login') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors px-3 py-1.5">
                            Login
                        </a>
                        <a href="{{ url('/register') }}" class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md px-3.5 py-1.5 transition-colors">
                            Register
                        </a>
                    </div>
                @endauth

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
            @auth
                <div class="nav-auth-only space-y-1">
                    <div class="flex items-center px-3 mb-3">
                        <div class="nav-user-initial w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center border border-indigo-200">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="ml-3">
                            <div class="nav-user-name text-sm font-medium text-gray-800">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <a href="{{ url('/profile') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">
                        Profile
                    </a>
                    <a href="{{ url('/orders') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">
                        Orders
                    </a>
                    <a href="{{ url('/addresses') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">
                        Addresses
                    </a>
                    <button type="button" onclick="handleSignOut()" class="w-full text-left block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">
                        Logout
                    </button>
                </div>
            @else
                <!-- Client-Side Auth Container -->
                <div class="nav-auth-only hidden space-y-1">
                    <div class="flex items-center px-3 mb-3">
                        <div class="nav-user-initial w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center border border-indigo-200">
                            U
                        </div>
                        <div class="ml-3">
                            <div class="nav-user-name text-sm font-medium text-gray-800">User</div>
                            <div class="text-xs text-gray-500">Authenticated</div>
                        </div>
                    </div>
                    <a href="{{ url('/profile') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">
                        Profile
                    </a>
                    <a href="{{ url('/orders') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">
                        Orders
                    </a>
                    <a href="{{ url('/addresses') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">
                        Addresses
                    </a>
                    <button type="button" onclick="handleSignOut()" class="w-full text-left block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">
                        Sign Out
                    </button>
                </div>

                <!-- Guest Links -->
                <div class="nav-guest-only space-y-2 pt-1">
                    <a href="{{ url('/login') }}" class="block text-center w-full px-3 py-2 rounded-md text-base font-medium text-gray-700 border border-gray-300 hover:bg-gray-50">
                        Login
                    </a>
                    <a href="{{ url('/register') }}" class="block text-center w-full px-3 py-2 rounded-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Register
                    </a>
                </div>
            @endauth
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerIcon = document.getElementById('hamburger-icon');
        const closeIcon = document.getElementById('close-icon');

        if (toggleBtn && mobileMenu) {
            toggleBtn.addEventListener('click', function () {
                const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                toggleBtn.setAttribute('aria-expanded', !isExpanded);
                mobileMenu.classList.toggle('hidden');
                hamburgerIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
            });
        }
    });
</script>
