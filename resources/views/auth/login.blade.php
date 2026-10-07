<x-guest-layout>
    <!-- Card Header -->
    <div class="mb-7 text-center">
        <h1 class="text-2xl font-black text-navy-950 tracking-tight">Welcome back</h1>
        <p class="text-xs text-slate-500 mt-1.5">Sign in to your account to continue shopping</p>
    </div>

    <!-- Session Status / Warning Alert -->
    @if(session('status'))
        <div class="mb-5 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" onsubmit="const btn=this.querySelector('button[type=submit]'); if(btn){ btn.disabled=true; btn.classList.add('opacity-75'); }">
        @csrf
        @if(request('redirect'))
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
        @endif

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
