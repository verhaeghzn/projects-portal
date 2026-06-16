<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') - {{ config('app.name', 'Projects Portal') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/gilroy-bold" rel="stylesheet">

    @php
        $hasVite = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
    @endphp

    @if ($hasVite)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body { font-family: 'Lato', ui-sans-serif, system-ui, sans-serif; }
        .font-heading { font-family: 'Gilroy', ui-sans-serif, system-ui, sans-serif; font-weight: 700; }
    </style>

    @yield('head')
</head>
<body class="min-h-screen bg-gray-50 text-tue-black">
<main class="min-h-screen flex items-center justify-center px-4 py-10 sm:py-16">
    <div class="w-full max-w-2xl">
        <div class="flex items-center justify-between gap-4 mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                <img src="{{ asset('assets/logos/tue_logo.svg') }}" alt="TU/e" class="h-8 w-auto">
                <span class="sr-only">{{ config('app.name', 'Projects Portal') }}</span>
            </a>

            <a href="{{ route('home') }}" class="text-sm text-tue-gray hover:text-primary">
                Home
            </a>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-10">
            <p class="text-xs font-semibold text-tue-gray tracking-widest">
                HTTP @yield('code')
            </p>

            <h1 class="mt-2 text-2xl sm:text-3xl font-heading text-tue-black">
                @yield('heading')
            </h1>

            <p class="mt-3 text-base text-tue-gray leading-relaxed">
                @yield('message')
            </p>

            <div class="mt-7 flex flex-col sm:flex-row gap-3">
                @yield('actions')
            </div>

            @hasSection('details')
                <div class="mt-7 pt-6 border-t border-gray-100">
                    <div class="text-xs text-tue-gray">
                        @yield('details')
                    </div>
                </div>
            @endif
        </div>

        <p class="mt-6 text-center text-xs text-tue-gray">
            &copy; {{ date('Y') }} TU/e
        </p>
    </div>
</main>
</body>
</html>
