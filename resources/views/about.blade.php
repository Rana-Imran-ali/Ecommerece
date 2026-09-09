@extends('layouts.app')

@section('title', 'About Us - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-12">
    <!-- Hero Banner -->
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 rounded-3xl text-white p-8 sm:p-16 shadow-xl">
        <div class="relative z-10 max-w-3xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-md mb-4">
                ✨ Crafting Premium Shopping Experiences
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                Empowering modern living with curated excellence.
            </h1>
            <p class="mt-4 text-indigo-100 text-base sm:text-lg leading-relaxed">
                Founded with a passion for quality and seamless digital shopping, we deliver top-tier products directly to your doorstep with speed, transparency, and care.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ url('/products') }}" class="px-6 py-3 rounded-xl bg-white text-indigo-900 font-bold text-sm shadow-md hover:bg-indigo-50 transition-all">
                    Explore Catalog
                </a>
                <a href="{{ url('/contact') }}" class="px-6 py-3 rounded-xl bg-white/10 text-white font-semibold text-sm border border-white/20 hover:bg-white/20 transition-all">
                    Get in Touch
                </a>
            </div>
        </div>

        <!-- Decorative background glow -->
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Impact & Metrics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-black text-indigo-600">10k+</div>
            <div class="text-xs sm:text-sm font-semibold text-gray-700 mt-1">Happy Customers</div>
            <p class="text-xs text-gray-400 mt-1">Across 30+ countries</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-black text-purple-600">50k+</div>
            <div class="text-xs sm:text-sm font-semibold text-gray-700 mt-1">Orders Delivered</div>
            <p class="text-xs text-gray-400 mt-1">Safe & fast fulfillment</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-black text-emerald-600">99.8%</div>
            <div class="text-xs sm:text-sm font-semibold text-gray-700 mt-1">Satisfaction Rate</div>
            <p class="text-xs text-gray-400 mt-1">Verified reviews</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-black text-amber-500">24/7</div>
            <div class="text-xs sm:text-sm font-semibold text-gray-700 mt-1">Customer Support</div>
            <p class="text-xs text-gray-400 mt-1">Always here for you</p>
        </div>
    </div>

    <!-- Our Core Values -->
    <div>
        <div class="text-center max-w-2xl mx-auto mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">What Drives Us Every Day</h2>
            <p class="text-sm text-gray-500 mt-2">Built upon pillars of reliability, quality, and human-centric service.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm hover:border-indigo-300 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Uncompromised Quality</h3>
                <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                    Every product listed in our inventory undergoes rigorous quality checks before it's approved and shipped out.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm hover:border-indigo-300 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Lightning-Fast Delivery</h3>
                <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                    Optimized warehouse routing and modern fulfillment ensure your packages arrive securely and on schedule.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm hover:border-indigo-300 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Guaranteed Security</h3>
                <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                    Bank-grade encryption, secure checkout channels, and protected payment gateways safeguard your financial data.
                </p>
            </div>
        </div>
    </div>

    <!-- Story & Vision -->
    <div class="bg-white rounded-3xl border border-gray-200 p-8 sm:p-12 shadow-sm grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div>
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Our Journey</span>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2 tracking-tight">
                From a small garage ambition to a global e-commerce hub.
            </h2>
            <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                What began in 2020 as a quest to simplify online shopping has blossomed into an all-in-one ecosystem trusted by thousands of customers worldwide. We constantly invest in user experience, supply chain technology, and sustainable packaging.
            </p>
            <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                Our mission is simple: offer hand-picked products that combine aesthetics, functionality, and lasting durability at fair prices.
            </p>

            <div class="mt-6 border-t border-gray-100 pt-6 flex items-center space-x-6">
                <div>
                    <div class="text-lg font-bold text-gray-900">Zero Hassle</div>
                    <div class="text-xs text-gray-500">30-day money-back guarantee</div>
                </div>
                <div class="border-l border-gray-200 pl-6">
                    <div class="text-lg font-bold text-gray-900">Eco-Friendly</div>
                    <div class="text-xs text-gray-500">100% recyclable shipping boxes</div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-tr from-indigo-50 to-purple-50 rounded-2xl p-6 sm:p-8 border border-indigo-100/60">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Our Core Promises</h3>
            <ul class="space-y-4 text-sm text-gray-600">
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-500 font-bold">✓</span>
                    <span><strong>100% Genuine Products:</strong> Sourced directly from verified creators and reputable brands.</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-500 font-bold">✓</span>
                    <span><strong>Live Order Tracking:</strong> Real-time milestone updates from checkout through delivery.</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-500 font-bold">✓</span>
                    <span><strong>Responsive Human Support:</strong> Dedicated specialists ready to resolve inquiries within minutes.</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-500 font-bold">✓</span>
                    <span><strong>Privacy First:</strong> Your personal data is never sold or shared with unauthorized third parties.</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-indigo-600 rounded-3xl p-8 sm:p-12 text-center text-white shadow-lg">
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Ready to upgrade your shopping journey?</h2>
        <p class="mt-2 text-indigo-100 text-sm sm:text-base max-w-xl mx-auto">
            Discover our curated catalog today with seasonal discounts and quick door-to-door delivery.
        </p>
        <div class="mt-6 flex justify-center gap-4">
            <a href="{{ url('/products') }}" class="px-6 py-3 rounded-xl bg-white text-indigo-700 font-bold text-sm shadow hover:bg-indigo-50 transition-all">
                Shop Now
            </a>
            <a href="{{ url('/contact') }}" class="px-6 py-3 rounded-xl bg-indigo-700 text-white font-semibold text-sm border border-indigo-500 hover:bg-indigo-800 transition-all">
                Contact Us
            </a>
        </div>
    </div>
</div>
@endsection
