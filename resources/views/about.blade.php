@extends('layouts.app')

@section('title', 'About Us - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-12">
    <!-- Hero Banner -->
    <div class="relative overflow-hidden bg-gradient-to-br from-navy-975 via-navy-900 to-navy-850 rounded-3xl border border-navy-800 text-white p-8 sm:p-16 shadow-2xl">
        <div class="relative z-10 max-w-3xl space-y-4">
            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-900/60 text-blue-300 border border-blue-700/50 shadow-inner">
                ✨ Crafting Premium Shopping Experiences
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight text-white">
                Empowering modern living with curated excellence.
            </h1>
            <p class="text-slate-300 text-base sm:text-lg leading-relaxed">
                Founded with a dedication to authentic quality and seamless digital retail, we deliver top-tier products directly to your doorstep with speed, transparency, and care.
            </p>
            <div class="pt-4 flex flex-wrap gap-4">
                <a href="{{ url('/products') }}" class="px-6 py-3.5 rounded-xl bg-white text-navy-950 font-bold text-xs shadow-md hover:bg-slate-100 transition-all">
                    Explore Catalog &rarr;
                </a>
                <a href="{{ url('/contact') }}" class="px-6 py-3.5 rounded-xl bg-white/10 text-white font-bold text-xs border border-white/20 hover:bg-white/20 transition-all backdrop-blur-sm">
                    Get in Touch
                </a>
            </div>
        </div>

        <!-- Decorative background glow -->
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Impact & Metrics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-extrabold text-blue-600">10k+</div>
            <div class="text-xs sm:text-sm font-bold text-navy-950 mt-1">Happy Shoppers</div>
            <p class="text-xs text-slate-400 mt-0.5">Across 30+ regions</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-extrabold text-indigo-600">50k+</div>
            <div class="text-xs sm:text-sm font-bold text-navy-950 mt-1">Orders Shipped</div>
            <p class="text-xs text-slate-400 mt-0.5">Fast &amp; verified fulfillment</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-extrabold text-emerald-600">99.8%</div>
            <div class="text-xs sm:text-sm font-bold text-navy-950 mt-1">Satisfaction Rate</div>
            <p class="text-xs text-slate-400 mt-0.5">Verified customer ratings</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm text-center">
            <div class="text-3xl sm:text-4xl font-extrabold text-amber-500">24/7</div>
            <div class="text-xs sm:text-sm font-bold text-navy-950 mt-1">Dedicated Care</div>
            <p class="text-xs text-slate-400 mt-0.5">WhatsApp &amp; AI support</p>
        </div>
    </div>

    <!-- Our Core Values -->
    <div class="space-y-8">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Our Standards</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-navy-950 tracking-tight">What Drives Us Every Day</h2>
            <p class="text-xs sm:text-sm text-slate-500">Built upon pillars of authenticity, security, and human-centric service.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-2xl border border-slate-200/90 shadow-xs hover:border-blue-400 hover:shadow-lg transition-all">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-5 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-navy-950">Guaranteed Authenticity</h3>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Every product listed in our inventory undergoes rigorous authenticity and quality inspections before it is approved and dispatched.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200/90 shadow-xs hover:border-blue-400 hover:shadow-lg transition-all">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-5 border border-indigo-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-navy-950">Rapid Fulfillment</h3>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Automated warehouse routing and express fulfillment logistics ensure your orders are packed securely and delivered promptly.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200/90 shadow-xs hover:border-blue-400 hover:shadow-lg transition-all">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-navy-950">Encrypted Payments</h3>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Stripe 3D-Secure authentication, bank-grade encryption, and multi-factor safety protocols protect every financial transaction.
                </p>
            </div>
        </div>
    </div>

    <!-- Story & Vision -->
    <div class="bg-white rounded-3xl border border-slate-200/90 p-8 sm:p-12 shadow-sm grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div>
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Our Story</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-navy-950 mt-1.5 tracking-tight">
                From a small ambition to a premier e-commerce destination.
            </h2>
            <p class="mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                What began as a vision to eliminate online shopping uncertainty has blossomed into an all-in-one platform trusted by thousands of customers. We constantly invest in customer experience, transparent pricing, and sustainable fulfillment.
            </p>
            <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                Our mission is simple: provide curated products that blend aesthetics, high functionality, and lasting durability at fair and honest prices.
            </p>

            <div class="mt-6 border-t border-slate-100 pt-6 flex items-center space-x-8">
                <div>
                    <div class="text-base font-extrabold text-navy-950">Zero Friction</div>
                    <div class="text-xs text-slate-500 font-semibold">30-day money-back guarantee</div>
                </div>
                <div class="border-l border-slate-200 pl-8">
                    <div class="text-base font-extrabold text-navy-950">Safe Sourcing</div>
                    <div class="text-xs text-slate-500 font-semibold">Verified supplier chain</div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 rounded-2xl p-7 border border-blue-100/80 shadow-xs">
            <h3 class="text-base font-extrabold text-navy-950 mb-4">Our Customer Commitment</h3>
            <ul class="space-y-4 text-xs sm:text-sm text-slate-600">
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-600 font-bold text-base leading-none">✓</span>
                    <span><strong>100% Genuine Inventory:</strong> Direct sourcing guarantees manufacturer warranties and authenticity.</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-600 font-bold text-base leading-none">✓</span>
                    <span><strong>Live Order Tracking:</strong> Milestone alerts from confirmed payment through doorstep delivery.</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-600 font-bold text-base leading-none">✓</span>
                    <span><strong>Real Support Specialists:</strong> Rapid resolution via WhatsApp and email ticketing.</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="text-emerald-600 font-bold text-base leading-none">✓</span>
                    <span><strong>Privacy by Design:</strong> Your personal information is never sold or shared with third parties.</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 rounded-3xl p-8 sm:p-12 text-center text-white shadow-xl">
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Ready to elevate your shopping experience?</h2>
        <p class="mt-2 text-blue-100 text-xs sm:text-sm max-w-xl mx-auto">
            Discover our curated catalog today with seasonal discounts and rapid door-to-door delivery.
        </p>
        <div class="mt-6 flex justify-center gap-4">
            <a href="{{ url('/products') }}" class="px-6 py-3.5 rounded-xl bg-white text-navy-950 font-bold text-xs shadow-md hover:bg-slate-100 transition-all">
                Shop Catalog &rarr;
            </a>
            <a href="{{ url('/contact') }}" class="px-6 py-3.5 rounded-xl bg-blue-900/60 text-white font-bold text-xs border border-blue-400/40 hover:bg-blue-900 transition-all">
                Contact Support
            </a>
        </div>
    </div>
</div>
@endsection
