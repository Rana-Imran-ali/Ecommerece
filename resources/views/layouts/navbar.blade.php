<nav class="bg-navy-950/95 backdrop-blur-md border-b border-navy-800/80 sticky top-0 z-50 transition-all duration-200 shadow-lg shadow-navy-950/30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 sm:h-20 items-center">
            <!-- Left: Brand / Logo & Desktop Links -->
            <div class="flex items-center space-x-6 lg:space-x-8">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group py-2">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-md shadow-blue-600/30 group-hover:scale-105 group-hover:shadow-blue-500/50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-xl font-extrabold text-white tracking-tight flex items-center">
                        {{ config('app.name', 'EStore') }}<span class="text-blue-400">.</span>
                    </span>
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="{{ url('/') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->is('/') ? 'text-white bg-blue-600/90 shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Home
                    </a>
                    <a href="{{ url('/shop') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->is('shop*') ? 'text-white bg-blue-600/90 shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Shop
                    </a>
                    <a href="{{ url('/products') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->is('products*') ? 'text-white bg-blue-600/90 shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Products
                    </a>
                    <a href="{{ url('/categories') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->is('categories*') ? 'text-white bg-blue-600/90 shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Categories
                    </a>
                    <a href="{{ url('/about') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->is('about*') ? 'text-white bg-blue-600/90 shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        About
                    </a>
                    <a href="{{ url('/contact') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->is('contact*') ? 'text-white bg-blue-600/90 shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Contact
                    </a>
                    <a href="{{ url('/chat') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-bold transition-all duration-200 {{ request()->is('chat*') ? 'text-white bg-gradient-to-r from-blue-600 to-indigo-600 shadow-md shadow-blue-600/30' : 'text-blue-300 bg-blue-950/60 border border-blue-800/50 hover:text-white hover:bg-blue-600 hover:border-transparent' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        <span>AI Assistant</span>
                    </a>
                </div>
            </div>

            @php
                $isAuth = auth()->check();
                $user = $isAuth ? auth()->user() : null;
                $avatarUrl = $user?->avatar
                    ? asset('storage/' . $user->avatar)
                    : ($user ? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=1d4ed8&color=ffffff&size=64&rounded=true&bold=true' : '');
                $waPhone = preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', env('WHATSAPP_SUPPORT_PHONE', '18005550199')));
                $waDefaultMsg = rawurlencode(config('whatsapp.default_message', 'Hello! I have a question regarding your store.'));
            @endphp

            <!-- Right: Search, Wishlist, Cart, WhatsApp Support, Auth Flow & Mobile Toggle -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- 1. Search Action Button -->
                <a href="{{ url('/search') }}"
                   class="p-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-white/10 border border-transparent hover:border-white/10 transition-all {{ request()->is('search*') ? 'text-white bg-blue-600/90' : '' }}"
                   title="Search catalog">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </a>

                <!-- 2. Wishlist Action Button -->
                <a href="{{ url('/wishlist') }}"
                   class="relative p-2.5 rounded-xl text-slate-300 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition-all {{ request()->is('wishlist*') ? 'text-rose-400 bg-rose-500/10' : '' }}"
                   title="Wishlist">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span class="nav-wishlist-badge hidden absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-rose-500 rounded-full min-w-[18px] shadow-sm">
                        0
                    </span>
                </a>

                <!-- 3. Cart Dropdown Button -->
                <div class="relative" id="cart-dropdown-wrap">
                    <button type="button"
                            id="cart-menu-btn"
                            class="relative p-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-white/10 border border-transparent hover:border-white/10 transition-all {{ request()->is('cart*') ? 'text-white bg-blue-600/90' : '' }}"
                            aria-haspopup="true"
                            aria-expanded="false"
                            title="Shopping Cart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="nav-cart-badge hidden absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-blue-500 rounded-full min-w-[18px] shadow-sm">
                            0
                        </span>
                    </button>

                    <!-- Cart Mini-Dropdown -->
                    <div id="cart-dropdown"
                         class="hidden absolute right-0 top-full mt-3 w-88 bg-white border border-slate-200 rounded-2xl shadow-2xl z-50 overflow-hidden"
                         style="min-width:20rem;">

                        <!-- Dropdown Header -->
                        <div class="flex items-center justify-between px-5 py-4 bg-navy-950 text-white border-b border-navy-800">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-bold tracking-tight">Shopping Bag</span>
                            </div>
                            <span id="cart-dropdown-count" class="text-xs font-semibold text-blue-300 bg-blue-900/60 px-2.5 py-0.5 rounded-full border border-blue-700/50">0 items</span>
                        </div>

                        <!-- Cart Items List -->
                        <div id="cart-dropdown-items" class="max-h-72 overflow-y-auto divide-y divide-slate-100 py-1 bg-white">
                            <!-- Populated by JS -->
                            <div id="cart-dropdown-empty" class="flex flex-col items-center justify-center py-10 px-4 text-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-slate-700 font-semibold">Your cart is empty</p>
                                <p class="text-xs text-slate-400 mt-0.5">Explore our catalog and find great deals</p>
                            </div>
                        </div>

                        <!-- Dropdown Footer -->
                        <div id="cart-dropdown-footer" class="hidden border-t border-slate-100 px-5 py-4 bg-slate-50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Subtotal</span>
                                <span id="cart-dropdown-subtotal" class="text-base font-extrabold text-navy-950">$0.00</span>
                            </div>
                            <a href="{{ url('/cart') }}"
                               class="block w-full text-center py-2.5 px-4 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-blue-700/20">
                                View Bag &amp; Checkout &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 4. WhatsApp Icon Button -->
                <a href="https://wa.me/{{ $waPhone }}?text={{ $waDefaultMsg }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="p-2.5 rounded-xl text-emerald-400 hover:text-white hover:bg-emerald-600/20 border border-transparent hover:border-emerald-500/30 transition-all"
                   title="WhatsApp Support">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 1.891.526 3.662 1.438 5.177L2 22l4.982-1.408A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.2c-1.637 0-3.16-.487-4.437-1.325l-.318-.21-2.96.837.854-2.883-.231-.334A8.16 8.16 0 013.8 12c0-4.521 3.679-8.2 8.2-8.2s8.2 3.679 8.2 8.2-3.679 8.2-8.2 8.2z"/>
                    </svg>
                </a>

                <!-- 5. Guest Links (Desktop) -->
                @guest
                <div class="hidden md:flex items-center space-x-2 pl-3 ml-1 border-l border-navy-800" id="nav-guest-area">
                    <a href="{{ url('/login') }}" class="text-sm font-semibold text-slate-200 hover:text-white transition-colors px-3.5 py-2 rounded-xl hover:bg-white/10">
                        Login
                    </a>
                    <a href="{{ url('/register') }}" class="text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl px-4 py-2 transition-all shadow-md shadow-blue-600/30 hover:shadow-blue-600/50">
                        Register
                    </a>
                </div>
                @endguest

                <!-- 6. Authenticated User Flow (Desktop) -->
                @auth
                <div class="hidden md:flex items-center space-x-2 pl-3 ml-1 border-l border-navy-800" id="nav-auth-area">
                    <!-- Direct My Account / Profile Button -->
                    <a href="{{ url('/account') }}"
                       id="nav-account-btn"
                       class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl text-sm font-semibold bg-navy-850 hover:bg-navy-800 text-white border border-blue-500/30 transition-all shadow-sm group"
                       title="Open My Account">
                        <img src="{{ $avatarUrl }}"
                             alt="{{ $user?->name ?? 'User' }}"
                             class="nav-user-avatar w-7 h-7 rounded-full object-cover border border-blue-400 group-hover:scale-105 transition-transform flex-shrink-0">
                        <span class="nav-account-text font-medium text-xs sm:text-sm text-slate-100 max-w-[100px] truncate">{{ $user?->name ?? 'Account' }}</span>
                    </a>

                    <!-- Account Quick-Access Dropdown Toggle -->
                    <div class="relative" id="account-dropdown-wrap">
                        <button type="button"
                                id="account-menu-btn"
                                class="p-2 text-slate-400 hover:text-white hover:bg-white/10 rounded-xl transition-colors focus:outline-none"
                                aria-haspopup="true"
                                aria-expanded="false"
                                title="Account Quick Menu">
                            <svg class="w-4 h-4 transition-transform" id="account-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="account-dropdown"
                             class="hidden absolute right-0 top-full mt-3 w-60 bg-white border border-slate-200 rounded-2xl shadow-2xl py-1.5 z-50 overflow-hidden">
                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Signed in as</div>
                                <div class="nav-user-name text-sm font-extrabold text-navy-950 truncate">{{ $user?->name ?? 'Customer' }}</div>
                                <div class="text-xs text-slate-500 truncate mt-0.5">{{ $user?->email ?? '' }}</div>
                            </div>
                            <div class="py-1">
                                <a href="{{ url('/account') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Dashboard Overview
                                </a>
                                <a href="{{ url('/account') }}#profile" onclick="sessionStorage.setItem('account_tab','profile')" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Profile Details
                                </a>
                                <a href="{{ url('/account') }}#orders" onclick="sessionStorage.setItem('account_tab','orders')" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    My Orders
                                </a>
                                <a href="{{ url('/account') }}#wishlist" onclick="sessionStorage.setItem('account_tab','wishlist')" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                    My Wishlist
                                </a>
                                <a href="{{ url('/account') }}#addresses" onclick="sessionStorage.setItem('account_tab','addresses')" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Addresses
                                </a>
                            </div>
                            <div class="border-t border-slate-100 pt-1">
                                <button type="button" onclick="handleSignOut()" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 font-semibold transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endauth

                <!-- Mobile Menu Button -->
                <button type="button"
                        id="mobile-menu-toggle"
                        aria-controls="mobile-menu"
                        aria-expanded="false"
                        class="md:hidden p-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-white/10 focus:outline-none ml-1">
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
    <div id="mobile-menu" class="hidden md:hidden border-t border-navy-800 bg-navy-950 text-white px-4 py-4 space-y-3">
        <div class="space-y-1">
            <a href="{{ url('/') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('/') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                Home
            </a>
            <a href="{{ url('/shop') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('shop*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                Shop
            </a>
            <a href="{{ url('/products') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('products*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                Products
            </a>
            <a href="{{ url('/categories') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('categories*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                Categories
            </a>
            <a href="{{ url('/about') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('about*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                About
            </a>
            <a href="{{ url('/contact') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('contact*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                Contact
            </a>
            <a href="{{ url('/chat') }}"
               class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-sm font-bold {{ request()->is('chat*') ? 'bg-blue-600 text-white' : 'text-blue-300 bg-blue-950/60 border border-blue-800/50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                AI Assistant
            </a>
            <a href="{{ url('/wishlist') }}"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('wishlist*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>Wishlist</span>
                <span class="nav-wishlist-badge hidden px-2 py-0.5 text-xs font-bold text-white bg-rose-500 rounded-full">0</span>
            </a>
            <a href="{{ url('/cart') }}"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('cart*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>Shopping Cart</span>
                <span class="nav-cart-badge hidden px-2 py-0.5 text-xs font-bold text-white bg-blue-500 rounded-full">0</span>
            </a>
            <a href="https://wa.me/{{ $waPhone }}?text={{ $waDefaultMsg }}"
               target="_blank"
               rel="noopener noreferrer"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold text-emerald-300 bg-emerald-950/60 border border-emerald-800/40">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 fill-[#25D366]" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 1.891.526 3.662 1.438 5.177L2 22l4.982-1.408A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.2c-1.637 0-3.16-.487-4.437-1.325l-.318-.21-2.96.837.854-2.883-.231-.334A8.16 8.16 0 013.8 12c0-4.521 3.679-8.2 8.2-8.2s8.2 3.679 8.2 8.2-3.679 8.2-8.2 8.2z"/>
                    </svg>
                    <span>WhatsApp Support</span>
                </span>
                <span class="text-xs font-bold text-emerald-400 bg-emerald-950 px-2 py-0.5 rounded-full border border-emerald-800/40">Active</span>
            </a>
        </div>

        <!-- Mobile User Account Section -->
        <div class="pt-3 border-t border-navy-800">
            @auth
            <div class="space-y-2">
                <div class="flex items-center px-3 py-2 bg-navy-900 rounded-xl border border-navy-800">
                    <img src="{{ $avatarUrl }}" alt="Avatar" class="nav-user-avatar w-10 h-10 rounded-full object-cover border border-blue-400 flex-shrink-0">
                    <div class="ml-3 min-w-0">
                        <div class="nav-user-name text-sm font-bold text-white truncate">{{ $user?->name ?? 'Customer' }}</div>
                        <div class="nav-user-email text-xs text-slate-400 truncate">{{ $user?->email ?? '' }}</div>
                    </div>
                </div>
                <a href="{{ url('/account') }}" class="flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-bold bg-blue-600 text-white hover:bg-blue-500 transition-colors shadow-md">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Account Dashboard
                    </span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="{{ url('/account') }}#orders" onclick="sessionStorage.setItem('account_tab','orders')" class="px-3 py-2 rounded-xl text-xs font-semibold text-center text-slate-200 bg-navy-900 hover:bg-navy-800 border border-navy-800">
                        My Orders
                    </a>
                    <a href="{{ url('/account') }}#wishlist" onclick="sessionStorage.setItem('account_tab','wishlist')" class="px-3 py-2 rounded-xl text-xs font-semibold text-center text-slate-200 bg-navy-900 hover:bg-navy-800 border border-navy-800">
                        Wishlist
                    </a>
                    <a href="{{ url('/account') }}#addresses" onclick="sessionStorage.setItem('account_tab','addresses')" class="px-3 py-2 rounded-xl text-xs font-semibold text-center text-slate-200 bg-navy-900 hover:bg-navy-800 border border-navy-800">
                        Addresses
                    </a>
                    <a href="{{ url('/account') }}#settings" onclick="sessionStorage.setItem('account_tab','settings')" class="px-3 py-2 rounded-xl text-xs font-semibold text-center text-slate-200 bg-navy-900 hover:bg-navy-800 border border-navy-800">
                        Settings
                    </a>
                </div>
                <button type="button" onclick="handleSignOut()" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-rose-400 hover:bg-rose-950/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </div>
            @endauth

            @guest
            <div class="space-y-2 pt-1">
                <a href="{{ url('/login') }}" class="block text-center w-full px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-200 border border-navy-800 hover:bg-white/5">
                    Login
                </a>
                <a href="{{ url('/register') }}" class="block text-center w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md">
                    Register
                </a>
            </div>
            @endguest
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

    /* ── Cart dropdown ── */
    const cartBtn      = document.getElementById('cart-menu-btn');
    const cartDropdown = document.getElementById('cart-dropdown');

    let cartDropdownLoaded = false;

    function closeCartDropdown() {
        if (!cartDropdown) return;
        cartDropdown.classList.add('hidden');
        if (cartBtn) cartBtn.setAttribute('aria-expanded', 'false');
    }

    function openCartDropdown() {
        if (!cartDropdown || !cartBtn) return;

        if (accountDropdown && !accountDropdown.classList.contains('hidden')) {
            accountDropdown.classList.add('hidden');
            if (accountBtn) accountBtn.setAttribute('aria-expanded', 'false');
            if (accountChevron) accountChevron.style.transform = '';
        }

        cartDropdown.classList.remove('hidden');
        cartBtn.setAttribute('aria-expanded', 'true');

        if (!cartDropdownLoaded) {
            loadCartDropdown();
        }
    }

    async function loadCartDropdown() {
        const itemsContainer = document.getElementById('cart-dropdown-items');
        const emptyState     = document.getElementById('cart-dropdown-empty');
        const footer         = document.getElementById('cart-dropdown-footer');
        const countEl        = document.getElementById('cart-dropdown-count');
        const subtotalEl     = document.getElementById('cart-dropdown-subtotal');

        if (!itemsContainer) return;

        emptyState && emptyState.classList.add('hidden');
        footer && footer.classList.add('hidden');
        itemsContainer.innerHTML = `
            <div class="px-4 py-8 flex justify-center">
                <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </div>`;

        if (typeof getAuthToken === 'function' && !getAuthToken()) {
            itemsContainer.innerHTML = '';
            if (emptyState) { emptyState.classList.remove('hidden'); itemsContainer.appendChild(emptyState); }
            if (countEl) countEl.textContent = '0 items';
            cartDropdownLoaded = true;
            return;
        }

        try {
            const res = typeof apiFetch === 'function'
                ? await apiFetch('/api/cart', { bypassCache: false })
                : await fetch('/api/cart', { headers: { 'Accept': 'application/json' } }).then(r => ({ ok: r.ok, data: r.json() }));

            const data = res?.data?.data || res?.data;
            const items    = data?.items    || [];
            const subtotal = data?.subtotal || 0;
            const total    = data?.total_items || 0;

            if (countEl) countEl.textContent = total === 1 ? '1 item' : `${total} items`;

            itemsContainer.innerHTML = '';

            if (!items.length) {
                const empty = document.getElementById('cart-dropdown-empty') || document.createElement('div');
                empty.id = 'cart-dropdown-empty';
                empty.className = 'flex flex-col items-center justify-center py-8 text-center';
                empty.innerHTML = `
                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm text-slate-600 font-semibold">Your cart is empty</p>
                    <p class="text-xs text-slate-400 mt-0.5">Add items to get started</p>`;
                itemsContainer.appendChild(empty);
                footer && footer.classList.add('hidden');
            } else {
                items.slice(0, 5).forEach(item => {
                    const product  = item.product || {};
                    const name     = product.name || 'Product';
                    const price    = parseFloat(product.price || 0);
                    const qty      = item.quantity || 1;
                    const imgObj   = product.primary_image || (product.images && product.images[0]) || null;
                    const imgSrc   = imgObj?.image_path
                        ? `/storage/${imgObj.image_path}`
                        : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=1d4ed8&color=ffffff&size=64&bold=true`;

                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors';
                    row.innerHTML = `
                        <img src="${imgSrc}" alt="${name}"
                             class="w-11 h-11 rounded-lg object-cover border border-slate-100 flex-shrink-0"
                             onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=1d4ed8&color=ffffff&size=64&bold=true'">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-navy-950 truncate">${name}</p>
                            <p class="text-xs text-slate-500">Qty: ${qty}</p>
                        </div>
                        <span class="text-xs font-bold text-blue-700 flex-shrink-0">$${(price * qty).toFixed(2)}</span>`;
                    itemsContainer.appendChild(row);
                });

                if (items.length > 5) {
                    const more = document.createElement('div');
                    more.className = 'text-center py-2 text-xs text-slate-400';
                    more.textContent = `+${items.length - 5} more item${items.length - 5 > 1 ? 's' : ''}`;
                    itemsContainer.appendChild(more);
                }

                if (subtotalEl) subtotalEl.textContent = `$${parseFloat(subtotal).toFixed(2)}`;
                footer && footer.classList.remove('hidden');
            }

            cartDropdownLoaded = true;
        } catch (err) {
            console.warn('Cart dropdown load error:', err);
            itemsContainer.innerHTML = `<p class="text-xs text-rose-500 px-4 py-4">Could not load cart. <a href="/cart" class="underline text-blue-600">View cart page</a>.</p>`;
        }
    }

    if (cartBtn && cartDropdown) {
        cartBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = !cartDropdown.classList.contains('hidden');
            if (isOpen) {
                closeCartDropdown();
            } else {
                openCartDropdown();
            }
        });

        cartDropdown.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('click', closeCartDropdown);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeCartDropdown();
        });
    }

    document.addEventListener('cart:updated', function () {
        cartDropdownLoaded = false;
    });

    if (typeof updateNavAuthUI === 'function') {
        updateNavAuthUI();
    }
});
</script>
