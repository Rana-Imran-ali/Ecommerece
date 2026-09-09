@extends('layouts.app')

@section('title', 'Payment Cancelled - ' . config('app.name', 'EStore'))

@section('content')
<div class="max-w-2xl mx-auto py-16 px-4">
    <!-- Cancel Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        <!-- Orange Header Band -->
        <div class="bg-gradient-to-br from-orange-400 to-amber-500 px-8 py-10 text-center text-white">
            <!-- Icon -->
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold tracking-tight">Payment Cancelled</h1>
            <p class="mt-2 text-orange-100 text-sm">You cancelled the payment. No charge has been made to your card.</p>
        </div>

        <!-- Body -->
        <div class="px-8 py-8 space-y-6">

            <!-- Info Box -->
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800 space-y-1">
                <p class="font-semibold">What happened to my order?</p>
                <p>Your order was placed but is currently <strong>pending payment</strong>. It will be automatically cancelled if payment is not completed.</p>
                @if($orderId)
                <p class="text-xs text-amber-600 mt-1 font-mono">Order Reference: #{{ $orderId }}</p>
                @endif
            </div>

            <!-- Options -->
            <div class="space-y-3">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">What would you like to do?</h2>

                <!-- Retry Payment -->
                <a href="/checkout"
                   class="flex items-center justify-between w-full px-5 py-4 bg-indigo-600 hover:bg-indigo-700
                          text-white rounded-xl transition-colors group">
                    <div>
                        <p class="font-semibold text-sm">Try Payment Again</p>
                        <p class="text-xs text-indigo-200 mt-0.5">Return to checkout and retry with a different card</p>
                    </div>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <!-- View Orders -->
                <a href="/orders"
                   class="flex items-center justify-between w-full px-5 py-4 border border-gray-200
                          hover:bg-gray-50 rounded-xl transition-colors group">
                    <div>
                        <p class="font-semibold text-sm text-gray-800">View My Orders</p>
                        <p class="text-xs text-gray-500 mt-0.5">Check the status of this or previous orders</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <!-- Continue Shopping -->
                <a href="/products"
                   class="flex items-center justify-between w-full px-5 py-4 border border-gray-200
                          hover:bg-gray-50 rounded-xl transition-colors group">
                    <div>
                        <p class="font-semibold text-sm text-gray-800">Continue Shopping</p>
                        <p class="text-xs text-gray-500 mt-0.5">Browse more products</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <!-- Support note -->
            <p class="text-center text-xs text-gray-400">
                Need help?
                <a href="/contact" class="text-indigo-600 hover:underline font-medium">Contact our support team</a>
            </p>
        </div>
    </div>
</div>
@endsection
