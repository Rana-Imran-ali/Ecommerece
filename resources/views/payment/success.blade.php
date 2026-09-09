@extends('layouts.app')

@section('title', 'Payment Successful - ' . config('app.name', 'EStore'))

@section('content')
<div class="max-w-2xl mx-auto py-16 px-4">
    <!-- Success Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        <!-- Green Header Band -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 px-8 py-10 text-center text-white">
            <!-- Animated Checkmark -->
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4
                        animate-[bounce_1s_ease-in-out_1]">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold tracking-tight">Payment Successful!</h1>
            <p class="mt-2 text-emerald-100 text-sm">Your order has been confirmed and is being processed.</p>
        </div>

        <!-- Body -->
        <div class="px-8 py-8 space-y-6">

            <!-- Order Reference -->
            @if($orderId)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Order Reference</p>
                    <p class="text-lg font-bold text-gray-900 mt-0.5">#{{ $orderId }}</p>
                </div>
                <a href="/orders/{{ $orderId }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                          text-white text-sm font-semibold rounded-lg transition-colors">
                    View Order
                    <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            @endif

            <!-- What Happens Next -->
            <div class="space-y-3">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">What happens next?</h2>
                <div class="space-y-2">
                    @foreach([
                        ['icon' => '📦', 'text' => 'Your order is now being prepared by our warehouse team.'],
                        ['icon' => '🚚', 'text' => 'You will receive a shipping confirmation once dispatched.'],
                        ['icon' => '📬', 'text' => 'Estimated delivery: 3–5 business days.'],
                    ] as $step)
                    <div class="flex items-start space-x-3 text-sm text-gray-600">
                        <span class="text-lg leading-none mt-0.5">{{ $step['icon'] }}</span>
                        <p>{{ $step['text'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Session ID (for support reference) -->
            @if($sessionId)
            <p class="text-[11px] text-gray-400 text-center font-mono break-all">
                Stripe Session: {{ $sessionId }}
            </p>
            @endif

            <!-- Actions -->
            <div class="grid grid-cols-2 gap-3 pt-2">
                <a href="/orders"
                   class="block text-center py-2.5 px-4 border border-gray-300 text-gray-700 text-sm
                          font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                    My Orders
                </a>
                <a href="/products"
                   class="block text-center py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white
                          text-sm font-semibold rounded-lg transition-colors">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
