<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900 min-h-screen bg-gradient-to-br from-navy-975 via-navy-900 to-navy-950 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Atmospheric Background Glow Elements -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10 space-y-6">
            <!-- Brand Header -->
            <div class="text-center">
                <a href="/" class="inline-flex items-center space-x-3 group">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-xl shadow-blue-600/40 group-hover:scale-105 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-extrabold text-white tracking-tight">
                        {{ config('app.name', 'EStore') }}<span class="text-blue-400">.</span>
                    </span>
                </a>
            </div>

            <!-- Auth Form Card -->
            <div class="bg-white rounded-3xl shadow-2xl shadow-navy-975/60 border border-slate-100 p-8 sm:p-10">
                {{ $slot }}
            </div>

            <!-- Footer Return Link -->
            <div class="text-center">
                <a href="/" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors inline-flex items-center gap-1.5">
                    &larr; Return to {{ config('app.name', 'EStore') }} Homepage
                </a>
            </div>
        </div>

        <script>
            // Automatically reload if restored from browser bfcache to guarantee fresh CSRF token
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        </script>
    </body>
</html>
