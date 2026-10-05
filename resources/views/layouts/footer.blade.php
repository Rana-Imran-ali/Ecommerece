<footer class="bg-navy-975 text-slate-300 border-t border-navy-800/80 mt-20">
    <!-- Value Proposition Highlights Banner -->
    <div class="border-b border-navy-800/70 bg-navy-950/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-400 flex items-center justify-center shrink-0 border border-blue-500/20 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Free Express Shipping</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Complimentary delivery on qualified orders</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-400 flex items-center justify-center shrink-0 border border-blue-500/20 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">100% Encrypted Checkout</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Bank-grade Stripe & 3D Secure protection</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-400 flex items-center justify-center shrink-0 border border-blue-500/20 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">30-Day Hassle-Free Returns</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Money-back guarantee on all eligible items</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-400 flex items-center justify-center shrink-0 border border-blue-500/20 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">24/7 Priority Support</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Direct chat via WhatsApp & AI assistance</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Columns -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
            <!-- Brand & Info -->
            <div class="lg:col-span-2 space-y-4">
                <a href="{{ url('/') }}" class="inline-flex items-center space-x-3 text-xl font-extrabold text-white tracking-tight">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span>{{ config('app.name', 'EStore') }}<span class="text-blue-400">.</span></span>
                </a>
                <p class="text-sm text-slate-400 leading-relaxed max-w-sm">
                    Your premier online shopping destination for genuine products, live stock tracking, secure Stripe payments, and rapid doorstep delivery.
                </p>
                <div class="pt-2">
                    @php
                        $waPhone = preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', env('WHATSAPP_SUPPORT_PHONE', '18005550199')));
                    @endphp
                    <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center space-x-2 text-xs font-bold px-4 py-2 rounded-xl bg-emerald-950/80 text-emerald-300 border border-emerald-700/50 hover:bg-emerald-900 transition-all shadow-sm">
                        <svg class="w-4 h-4 fill-[#25D366]" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                        </svg>
                        <span>WhatsApp Help Desk</span>
                    </a>
                </div>
            </div>

            <!-- Quick Shop Links -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-100">Shop Catalog</h4>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="{{ url('/shop') }}" class="hover:text-white transition-colors">All Products</a></li>
                    <li><a href="{{ url('/categories') }}" class="hover:text-white transition-colors">Categories</a></li>
                    <li><a href="{{ url('/search') }}" class="hover:text-white transition-colors">Search Store</a></li>
                    <li><a href="{{ url('/wishlist') }}" class="hover:text-white transition-colors">My Wishlist</a></li>
                    <li><a href="{{ url('/cart') }}" class="hover:text-white transition-colors">Shopping Cart</a></li>
                </ul>
            </div>

            <!-- Customer Account -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-100">Customer Care</h4>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="{{ url('/account') }}" class="hover:text-white transition-colors">My Account</a></li>
                    <li><a href="{{ url('/account#orders') }}" class="hover:text-white transition-colors">Order Tracking</a></li>
                    <li><a href="{{ url('/addresses') }}" class="hover:text-white transition-colors">Shipping Addresses</a></li>
                    <li><a href="{{ url('/about') }}" class="hover:text-white transition-colors">About Us</a></li>
                    <li><a href="{{ url('/contact') }}" class="hover:text-white transition-colors">Help & Contact</a></li>
                </ul>
            </div>

            <!-- Trust & Security -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-100">Secure Payments</h4>
                <p class="text-xs text-slate-400 leading-relaxed">
                    We accept all major credit and debit cards, Stripe 3D-Secure checkout, and Cash on Delivery.
                </p>
                <div class="flex flex-wrap gap-2 pt-1 text-xs">
                    <span class="px-3 py-1 rounded-lg bg-navy-900 border border-navy-800 text-slate-300 font-semibold">Visa</span>
                    <span class="px-3 py-1 rounded-lg bg-navy-900 border border-navy-800 text-slate-300 font-semibold">Mastercard</span>
                    <span class="px-3 py-1 rounded-lg bg-navy-900 border border-navy-800 text-slate-300 font-semibold">Stripe</span>
                    <span class="px-3 py-1 rounded-lg bg-navy-900 border border-navy-800 text-slate-300 font-semibold">Cash On Delivery</span>
                </div>
            </div>
        </div>

        <!-- Bottom Copyright & Policies -->
        <div class="border-t border-navy-800/80 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'EStore') }}. All rights reserved.</p>
            <div class="flex items-center space-x-4">
                <a href="{{ url('/about') }}" class="hover:text-slate-300 transition-colors">Privacy Policy</a>
                <span>&bull;</span>
                <a href="{{ url('/about') }}" class="hover:text-slate-300 transition-colors">Terms of Service</a>
                <span>&bull;</span>
                <a href="{{ url('/contact') }}" class="hover:text-slate-300 transition-colors">Contact Support</a>
            </div>
        </div>
    </div>
</footer>
