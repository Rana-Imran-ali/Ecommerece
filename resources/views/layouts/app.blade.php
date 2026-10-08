<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Ecommerece'))</title>

        <!-- Fonts: Plus Jakarta Sans & Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900 selection:bg-blue-600 selection:text-white min-h-screen flex flex-col">
        <!-- Authentication State Hydration: Safe session-backed state for instant client-side navbar rendering -->
        @auth
            <script>
                window.IS_AUTHENTICATED = true;
                window.AUTH_USER = {!! json_encode([
                    'id'         => auth()->user()->id,
                    'name'       => auth()->user()->name,
                    'email'      => auth()->user()->email,
                    'role'       => auth()->user()->role ?? 'customer',
                    'avatar_url' => auth()->user()->avatar_url,
                ]) !!};
                try {
                    // Security fix: never store auth tokens in localStorage for web session users
                    localStorage.removeItem('ecommerce_auth_token');
                    sessionStorage.removeItem('logged_out');
                } catch(e) {}
            </script>
        @else
            <script>
                window.IS_AUTHENTICATED = false;
                window.AUTH_USER = null;
                try {
                    localStorage.removeItem('ecommerce_auth_token');
                    localStorage.removeItem('ecommerce_auth_user');
                } catch(e) {}
            </script>
        @endauth

        <div class="min-h-screen flex flex-col justify-between">
            <div>
                <!-- Storefront Header / Navbar -->
                @include('layouts.navbar')

                <!-- Page Heading (optional) -->
                @isset($header)
                    <header class="bg-white border-b border-slate-200/80 shadow-xs">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>

            <!-- Storefront Footer -->
            @include('layouts.footer')
        </div>

        <!-- Global API Helper & Stack Scripts -->
        <script src="{{ asset('js/api.js') }}"></script>
        @stack('scripts')

        <!-- WhatsApp Floating Chat Button -->
        @include('components.whatsapp-button')
    </body>
</html>
