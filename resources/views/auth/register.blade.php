<x-guest-layout>
    <!-- Card Header -->
    <div class="mb-7 text-center">
        <h1 class="text-2xl font-black text-navy-950 tracking-tight">Create your account</h1>
        <p class="text-xs text-slate-500 mt-1.5">Join thousands of happy shoppers today — it's free</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Your full name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Min 8 characters" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <!-- Terms notice -->
        <p class="text-[11px] text-slate-400 text-center leading-relaxed">
            By creating an account you agree to our
            <a href="#" class="text-blue-600 font-semibold">Terms of Service</a> and
            <a href="#" class="text-blue-600 font-semibold">Privacy Policy</a>.
        </p>

        <!-- Submit -->
        <div>
            <x-primary-button class="w-full justify-center py-3">
                {{ __('Create Free Account') }}
            </x-primary-button>
        </div>

        <!-- Login link -->
        <p class="text-center text-xs text-slate-500 pt-1">
            Already have an account?
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-navy-900 font-bold ml-1 transition-colors">Sign in instead &rarr;</a>
        </p>
    </form>
</x-guest-layout>
