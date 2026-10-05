<x-guest-layout>
    <!-- Card Header -->
    <div class="mb-7 text-center">
        <h1 class="text-2xl font-black text-navy-950 tracking-tight">Welcome back</h1>
        <p class="text-xs text-slate-500 mt-1.5">Sign in to your account to continue shopping</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-navy-950 focus:ring-navy-900" name="remember">
                <span class="text-xs font-semibold text-slate-600">{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-xs font-bold text-blue-600 hover:text-navy-900 transition-colors" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <!-- Submit -->
        <div class="pt-1">
            <x-primary-button class="w-full justify-center py-3">
                {{ __('Sign In to Account') }}
            </x-primary-button>
        </div>

        <!-- Register link -->
        <p class="text-center text-xs text-slate-500 pt-1">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-blue-600 hover:text-navy-900 font-bold ml-1 transition-colors">Create one free &rarr;</a>
        </p>
    </form>
</x-guest-layout>
