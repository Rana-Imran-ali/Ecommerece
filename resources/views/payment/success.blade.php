@extends('layouts.app')

@section('title', 'Payment Successful - ' . config('app.name', 'EStore'))

@section('content')
<div class="max-w-2xl mx-auto py-12 sm:py-16 px-4">
    <!-- Success Card -->
    <div class="bg-white rounded-3xl border border-navy-100 shadow-xl shadow-navy-950/5 overflow-hidden">

        <!-- Midnight Navy & Emerald Accent Header Band -->
        <div class="bg-gradient-to-br from-navy-975 via-navy-900 to-navy-950 px-8 py-10 text-center text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-radial-at-t from-emerald-500/10 via-transparent to-transparent pointer-events-none"></div>
            
            <!-- Animated Checkmark -->
            <div class="relative inline-flex items-center justify-center w-20 h-20 bg-emerald-500/20 border border-emerald-400/40 rounded-full mb-4 shadow-lg shadow-emerald-500/20">
                <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Payment Confirmed!</h1>
            <p class="mt-2 text-slate-300 text-sm max-w-md mx-auto">Your transaction was successful and your order has entered the fulfillment pipeline.</p>
        </div>

        <!-- Body -->
        <div class="px-6 sm:px-8 py-8 space-y-6">

            <!-- Order Reference -->
            @if($orderId)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 sm:p-5 bg-navy-50/60 rounded-2xl border border-navy-100 gap-3">
                <div>
                    <p class="text-xs text-navy-600 uppercase tracking-wider font-bold">Order Reference</p>
                    <p class="text-xl font-black text-navy-950 mt-0.5">#{{ $orderId }}</p>
                </div>
                <a href="/orders/{{ $orderId }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 bg-navy-950 hover:bg-navy-900 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                    Track Order
                    <svg class="ml-1.5 w-4 h-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            @endif

            <!-- What Happens Next -->
            <div class="space-y-3.5">
                <h2 class="text-xs font-bold text-navy-950 uppercase tracking-wider">What happens next?</h2>
                <div class="space-y-2.5">
                    @foreach([
                        ['icon' => '📦', 'title' => 'Warehouse Processing', 'text' => 'Our fulfillment center has received your order and is packing your items.'],
                        ['icon' => '🚚', 'title' => 'Courier Dispatch', 'text' => 'You will receive email & SMS tracking notifications as soon as courier takes over.'],
                        ['icon' => '📬', 'title' => 'Estimated Delivery', 'text' => 'Doorstep delivery usually takes between 3 to 5 business days.'],
                    ] as $step)
                    <div class="flex items-start gap-3 p-3 bg-slate-50/80 rounded-xl border border-slate-100">
                        <span class="text-xl leading-none mt-0.5">{{ $step['icon'] }}</span>
                        <div>
                            <p class="text-xs font-bold text-slate-900">{{ $step['title'] }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $step['text'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Session ID (for support reference) -->
            @if($sessionId)
            <p class="text-[11px] text-slate-400 text-center font-mono break-all bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                Stripe Reference: {{ $sessionId }}
            </p>
            @endif

            <!-- Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <a href="/orders"
                   class="block text-center py-3 px-4 border border-slate-200 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 transition-colors">
                    View All Orders
                </a>
                <a href="/products"
                   class="block text-center py-3 px-4 bg-navy-950 hover:bg-navy-900 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-navy-950/20">
                    Continue Shopping &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
