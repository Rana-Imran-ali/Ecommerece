<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <!-- Authentication Session Bridge: Run before navbar so auth state is immediately synchronized -->
        @auth
            @php
                $currentUser = auth()->user();
                $sessionUser = [
                    'id'         => $currentUser->id,
                    'name'       => $currentUser->name,
                    'email'      => $currentUser->email,
                    'role'       => $currentUser->role ?? 'customer',
                    'avatar_url' => $currentUser->avatar_url,
                ];
                // Reuse existing session token or create a dedicated web-session token
                $sessionToken = session('api_token');
                if (!$sessionToken) {
                    $currentUser->tokens()->where('name', 'web-session')->delete();
                    $sessionToken = $currentUser->createToken('web-session')->plainTextToken;
                    session(['api_token' => $sessionToken]);
                }
            @endphp
            <script>
                window.IS_AUTHENTICATED = true;
                window.AUTH_USER = @json($sessionUser);
                window.SESSION_TOKEN = @json($sessionToken);
                try {
                    localStorage.setItem('ecommerce_auth_token', window.SESSION_TOKEN);
                    localStorage.setItem('ecommerce_auth_user', JSON.stringify(window.AUTH_USER));
                    sessionStorage.removeItem('logged_out');
                } catch(e) {}
            </script>
        @else
            <script>
                window.IS_AUTHENTICATED = false;
                window.AUTH_USER = null;
                window.SESSION_TOKEN = null;
                try {
                    if (sessionStorage.getItem('logged_out')) {
                        localStorage.removeItem('ecommerce_auth_token');
                        localStorage.removeItem('ecommerce_auth_user');
                        sessionStorage.removeItem('logged_out');
                    }
                } catch(e) {}
            </script>
        @endauth

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navbar')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>

        <!-- Global API Helper & Stack Scripts -->
        <script src="{{ asset('js/api.js') }}"></script>
        @stack('scripts')

        <!-- WhatsApp Floating Chat Button -->
        @include('components.whatsapp-button')
    </body>
</html>
