<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2E7D32">
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        <link rel="apple-touch-icon" href="{{ url('/pwa/icon-192.png') }}">
        @if (auth()->check())
            <meta name="user-id" content="{{ auth()->id() }}">
        @endif
        <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper">
        <header class="sticky top-0 z-40 border-b border-ink-200/70 bg-white/80 backdrop-blur-lg">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
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
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-700 transition hover:bg-ink-100">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-forest-700">
                            Sign in
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status'))
                <x-flash>{{ session('status') }}</x-flash>
            @endif
            @yield('content')
        </main>

        @yield('scripts')
    </body>
</html>
