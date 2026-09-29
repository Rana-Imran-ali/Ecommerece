<footer class="bg-gray-900 text-gray-300 border-t border-gray-800 mt-16">
    <!-- Value Proposition Highlights Banner -->
    <div class="border-b border-gray-800 bg-gray-950/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-lg bg-indigo-900/60 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-700/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white">Free Standard Shipping</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Complimentary delivery on eligible orders</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-lg bg-indigo-900/60 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-700/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white">100% Secure Checkout</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Encrypted Stripe & card payments</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-lg bg-indigo-900/60 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-700/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white">30-Day Easy Returns</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Hassle-free refunds & exchanges</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-lg bg-indigo-900/60 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-700/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white">24/7 Dedicated Support</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Quick assistance via WhatsApp & email</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Columns -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
            <!-- Brand & Info -->
            <div class="lg:col-span-2 space-y-4">
                <a href="{{ url('/') }}" class="flex items-center space-x-2 text-xl font-bold text-white tracking-tight">
                    <svg class="w-7 h-7 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>{{ config('app.name', 'EStore') }}</span>
                </a>
                <p class="text-sm text-gray-400 leading-relaxed max-w-sm">
                    Your premier online destination for authentic products, transparent pricing, verified customer reviews, and fast doorstep delivery.
                </p>
                <div class="flex items-center space-x-3 pt-2">
                    @php
                        $waPhone = preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', env('WHATSAPP_SUPPORT_PHONE', '18005550199')));
                    @endphp
                    <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center space-x-2 text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-950/80 text-emerald-400 border border-emerald-700/50 hover:bg-emerald-900 transition-colors">
                        <svg class="w-4 h-4 fill-[#25D366]" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                        </svg>
                        <span>WhatsApp Support</span>
                    </a>
                </div>
            </div>

            <!-- Quick Shop Links -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-200">Shop Catalog</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="{{ url('/shop') }}" class="hover:text-white transition-colors">All Products</a></li>
                    <li><a href="{{ url('/categories') }}" class="hover:text-white transition-colors">Categories</a></li>
                    <li><a href="{{ url('/search') }}" class="hover:text-white transition-colors">Search Store</a></li>
                    <li><a href="{{ url('/wishlist') }}" class="hover:text-white transition-colors">My Wishlist</a></li>
                    <li><a href="{{ url('/cart') }}" class="hover:text-white transition-colors">Shopping Cart</a></li>
                </ul>
            </div>

            <!-- Customer Account -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-200">Customer Care</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="{{ url('/account') }}" class="hover:text-white transition-colors">My Account</a></li>
                    <li><a href="{{ url('/account#orders') }}" class="hover:text-white transition-colors">Order Tracking</a></li>
                    <li><a href="{{ url('/addresses') }}" class="hover:text-white transition-colors">Shipping Addresses</a></li>
                    <li><a href="{{ url('/about') }}" class="hover:text-white transition-colors">About Us</a></li>
                    <li><a href="{{ url('/contact') }}" class="hover:text-white transition-colors">Help & Contact</a></li>
                </ul>
            </div>

            <!-- Trust & Security -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-200">Payment & Trust</h4>
                <p class="text-xs text-gray-400 leading-relaxed">
                    We accept all major credit/debit cards, Stripe 3D-Secure payments, and Cash on Delivery.
                </p>
                <div class="flex flex-wrap gap-2 pt-1 text-xs">
                    <span class="px-2.5 py-1 rounded bg-gray-800 border border-gray-700 text-gray-300 font-medium">Visa</span>
                    <span class="px-2.5 py-1 rounded bg-gray-800 border border-gray-700 text-gray-300 font-medium">Mastercard</span>
                    <span class="px-2.5 py-1 rounded bg-gray-800 border border-gray-700 text-gray-300 font-medium">Stripe</span>
                    <span class="px-2.5 py-1 rounded bg-gray-800 border border-gray-700 text-gray-300 font-medium">Cash on Delivery</span>
                </div>
            </div>
        </div>

        <!-- Bottom Copyright & Policies -->
        <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 gap-3">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'EStore') }}. All rights reserved.</p>
            <div class="flex items-center space-x-4">
                <a href="{{ url('/about') }}" class="hover:text-gray-400 transition-colors">Privacy Policy</a>
                <span>&bull;</span>
                <a href="{{ url('/about') }}" class="hover:text-gray-400 transition-colors">Terms of Service</a>
                <span>&bull;</span>
                <a href="{{ url('/contact') }}" class="hover:text-gray-400 transition-colors">Contact Support</a>
            </div>
        </div>
    </div>
</footer>
