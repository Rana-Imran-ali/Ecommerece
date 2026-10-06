@extends('layouts.app')

@section('title', 'Payment Cancelled - ' . config('app.name', 'EStore'))

@section('content')
<div class="max-w-2xl mx-auto py-12 sm:py-16 px-4">
    <!-- Cancel Card -->
    <div class="bg-white rounded-3xl border border-navy-100 shadow-xl shadow-navy-950/5 overflow-hidden">

        <!-- Dark Navy Band with Amber Accent -->
        <div class="bg-gradient-to-br from-navy-975 via-navy-900 to-navy-950 px-8 py-10 text-center text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-radial-at-t from-amber-500/10 via-transparent to-transparent pointer-events-none"></div>

            <!-- Icon -->
            <div class="relative inline-flex items-center justify-center w-20 h-20 bg-amber-500/20 border border-amber-400/40 rounded-full mb-4 shadow-lg shadow-amber-500/20">
                <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Payment Cancelled</h1>
            <p class="mt-2 text-slate-300 text-sm max-w-md mx-auto">The payment process was not completed. No charges were made to your account or payment card.</p>
        </div>

        <!-- Body -->
        <div class="px-6 sm:px-8 py-8 space-y-6">

            <!-- Info Box -->
            <div class="p-4 bg-amber-50/80 border border-amber-200/80 rounded-2xl text-xs text-amber-900 space-y-1">
                <p class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Your items are safely saved in your shopping cart
                </p>
                <p class="text-amber-800">You can retry checkout anytime — no charges were made to your account or payment card.</p>
                @if($orderId)
                <p class="text-[11px] text-amber-700 font-mono pt-1">Reference ID: #{{ $orderId }}</p>
                @endif
            </div>

            <!-- Options -->
            <div class="space-y-3">
                <h2 class="text-xs font-bold text-navy-950 uppercase tracking-wider">What would you like to do?</h2>

                <!-- Retry Payment -->
                <a href="/checkout"
                   class="flex items-center justify-between w-full px-5 py-4 bg-navy-950 hover:bg-navy-900 text-white rounded-2xl transition-all shadow-md shadow-navy-950/20 group">
                    <div>
                        <p class="font-bold text-sm">Return to Checkout & Retry</p>
                        <p class="text-xs text-slate-300 mt-0.5">Complete your purchase using Stripe or Cash on Delivery</p>
                    </div>
                    <svg class="w-5 h-5 text-blue-300 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <!-- View Orders -->
                <a href="/orders"
                   class="flex items-center justify-between w-full px-5 py-4 border border-slate-200 hover:border-navy-300 hover:bg-slate-50 rounded-2xl transition-all group">
                    <div>
                        <p class="font-bold text-sm text-slate-800">View My Orders</p>
                        <p class="text-xs text-slate-500 mt-0.5">Check status of past or pending orders</p>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-navy-900 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <!-- Continue Shopping -->
                <a href="/products"
                   class="flex items-center justify-between w-full px-5 py-4 border border-slate-200 hover:border-navy-300 hover:bg-slate-50 rounded-2xl transition-all group">
                    <div>
                        <p class="font-bold text-sm text-slate-800">Continue Shopping</p>
                        <p class="text-xs text-slate-500 mt-0.5">Browse more trending products</p>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-navy-900 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <!-- Support note -->
            <p class="text-center text-xs text-slate-500">
                Experiencing technical issues?
                <a href="/contact" class="text-navy-600 hover:text-navy-900 underline font-semibold ml-1">Contact our 24/7 support team</a>
            </p>
        </div>
    </div>
</div>
@endsection
