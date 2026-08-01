<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Sign in') — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-paper px-4 py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-forest-600 font-heading text-xl font-bold text-white">W</span>
                <span class="font-heading text-xl font-semibold tracking-tight text-ink-900">Work<span class="text-forest-600">Ride</span></span>
            </a>

            <div class="rounded-2xl border border-ink-200 bg-white p-8 shadow-sm">
                @if (session('status'))
                    <x-flash>{{ session('status') }}</x-flash>
                @endif
                @yield('content')
            </div>

            <p class="mt-6 text-center text-xs text-ink-400">
                Built by amateurs, for the working class. From Abuja to the world.
            </p>
        </div>
    </body>
</html>
