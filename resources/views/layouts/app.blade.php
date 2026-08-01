<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="user-id" content="{{ auth()->user()->id }}">
        <meta name="theme-color" content="#2E7D32">
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        <link rel="apple-touch-icon" href="{{ url('/pwa/icon-192.png') }}">
        <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper">
        <header class="sticky top-0 z-40 border-b border-ink-200/70 bg-white/80 backdrop-blur-lg">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-forest-600 font-heading text-lg font-bold text-white">W</span>
                    <span class="font-heading text-lg font-semibold tracking-tight text-ink-900">Work<span class="text-forest-600">Ride</span></span>
                </a>

                <nav class="flex items-center gap-1 text-sm">
                    <x-nav-link :active="request()->routeIs('dashboard')" href="{{ route('dashboard') }}">Dashboard</x-nav-link>
                    <x-nav-link :active="request()->routeIs('trips.*')" href="{{ route('trips.index') }}">Trips</x-nav-link>
                    <x-nav-link :active="request()->routeIs('bookings.*')" href="{{ route('bookings.index') }}">My Rides</x-nav-link>
                    <x-nav-link :active="request()->routeIs('wallet.*')" href="{{ route('wallet.index') }}">Wallet</x-nav-link>
                    <x-nav-link :active="request()->routeIs('rewards.*')" href="{{ route('rewards.index') }}">Rewards</x-nav-link>
                    <x-nav-link :active="request()->routeIs('commodities.*')" href="{{ route('commodities.index') }}">Commodities</x-nav-link>
                    <x-nav-link :active="request()->routeIs('shop.*')" href="{{ route('shop.index') }}">Shop</x-nav-link>
                    <x-nav-link :active="request()->routeIs('impact.*')" href="{{ route('impact.index') }}">Impact</x-nav-link>
                    <x-nav-link :active="request()->routeIs('verification.*')" href="{{ route('verification.index') }}">Verify</x-nav-link>
                    <x-nav-link :active="request()->routeIs('road.map')" href="{{ route('road.map') }}">Road Map</x-nav-link>
                    @if (auth()->user()->isAdmin())
                        <x-nav-link :active="request()->routeIs('admin.*')" href="{{ route('admin.dashboard') }}">Control Tower</x-nav-link>
                    @endif
                </nav>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-xs font-medium text-ink-900">{{ auth()->user()->name }}</p>
                        <p class="font-mono text-[11px] text-ink-500">
                            L{{ auth()->user()->verification_level->value }} · {{ auth()->user()->verificationLevelLabel() }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-700 transition hover:bg-ink-100">
                            Sign out
                        </button>
                    </form>
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
