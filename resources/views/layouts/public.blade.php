<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if (auth()->check())
            <meta name="user-id" content="{{ auth()->id() }}">
        @endif
        <meta name="theme-color" content="#1B5E20">
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        <link rel="apple-touch-icon" href="{{ url('/pwa/icon-192.png') }}">
        <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper pt-safe pb-safe" x-data="darkMode()" x-init="init()">
        {{-- Guest-safe header — never reads auth()->user() unconditionally --}}
        <header class="sticky top-0 z-40 h-16 border-b border-ink-200/70 bg-white/80 backdrop-blur-lg">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4">
                <a href="{{ route('go') }}" class="flex items-center gap-2" aria-label="WorkRide Home">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-forest-600 font-heading text-lg font-bold text-white">W</span>
                    <span class="font-heading text-lg font-semibold tracking-tight text-ink-900">Work<span class="text-forest-600">Ride</span></span>
                </a>

                <div class="flex items-center gap-3">
                    @if (auth()->check())
                        <span class="hidden text-right sm:block">
                            <p class="text-xs font-medium text-ink-900">{{ auth()->user()->name }}</p>
                            <p class="font-mono text-[11px] text-ink-500">
                                L{{ auth()->user()->verification_level->value }} · {{ auth()->user()->verificationLevelLabel() }}
                            </p>
                        </span>
                        <a href="{{ route('dashboard') }}"
                           class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-700 transition hover:bg-ink-100">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-forest-700">
                            Sign in
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            @if (session('status'))
                <x-flash>{{ session('status') }}</x-flash>
            @endif
            @yield('content')
        </main>

        @yield('scripts')
    </body>
</html>