<x-guest-layout>
    <!-- Card Header -->
    <div class="mb-7 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-navy-50 text-navy-900 mb-4 border border-navy-100">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-black text-navy-950 tracking-tight">Forgot your password?</h1>
        <p class="text-xs text-slate-500 mt-2 max-w-xs mx-auto leading-relaxed">
            No problem — enter your email address and we'll send you a secure reset link.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Submit -->
        <div class="pt-1">
            <x-primary-button class="w-full justify-center py-3">
                {{ __('Send Password Reset Link') }}
            </x-primary-button>
        </div>

        <!-- Back to login -->
        <p class="text-center text-xs text-slate-500">
            Remembered your password?
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-navy-900 font-bold ml-1 transition-colors">Sign in &rarr;</a>
        </p>
    </form>
</x-guest-layout>
