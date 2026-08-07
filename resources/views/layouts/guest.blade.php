<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'OSA System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;800;900&family=Playfair+Display:ital@1&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-navy-gradient">
            <div class="flex flex-col items-center text-center text-white">
                <span class="w-16 h-16 rounded-full border-2 border-gold-500 flex items-center justify-center">
                    <x-heroicon-o-pencil class="w-7 h-7 text-gold-500" />
                </span>
                <p class="mt-4 text-xs uppercase tracking-[0.3em] text-slate-200">Office of Student Affairs</p>
                <h1 class="mt-1 font-display text-2xl font-extrabold uppercase tracking-wide">Activity Requests</h1>
                <p class="mt-1 text-xs italic font-wordmark text-gold-500">Ateneo de Zamboanga University</p>
            </div>

            <div class="w-full sm:max-w-md mt-8 px-6 py-6 bg-paper shadow-xl overflow-hidden rounded-xl">
                {{ $slot }}
            </div>

            <p class="mt-6 text-[11px] text-slate-200/70">Accounts are provisioned by the OSA office.</p>
        </div>
    </body>
</html>
