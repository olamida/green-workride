<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="user-id" content="{{ auth()->user()->id }}">
        <meta name="theme-color" content="#2E7D32">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="WorkRide">
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        <link rel="apple-touch-icon" href="{{ url('/pwa/icon-192.png') }}">
        <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper pb-16 lg:pb-0">
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

        <header class="sticky top-0 z-40 border-b border-ink-200/70 bg-white/80 backdrop-blur-lg">
            <div class="mx-auto flex h-14 max-w-[480px] items-center justify-between gap-4 px-4 sm:px-6 lg:max-w-5xl">
                <a href="{{ route('go') }}" class="flex shrink-0 items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-forest-600 font-heading text-base font-bold text-white">W</span>
                    <span class="font-heading text-base font-semibold tracking-tight text-ink-900">Work<span class="text-forest-600">Ride</span></span>
                </a>

                {{-- Primary destinations — kept deliberately small. Everything else
                     lives in ⌘K, the profile menu, or the mobile "More" sheet. --}}
                <nav class="hidden items-center gap-0.5 text-sm lg:flex">
                    <x-nav-link icon="map-pin" :active="request()->routeIs('go')" href="{{ route('go') }}">Go</x-nav-link>
                    <x-nav-link icon="route" :active="request()->routeIs('trips.*')" href="{{ route('trips.index') }}">Trips</x-nav-link>
                    <x-nav-link icon="ticket" :active="request()->routeIs('bookings.*')" href="{{ route('bookings.index') }}">My Rides</x-nav-link>
                    <x-nav-link icon="gift" :active="request()->routeIs('rewards.*')" href="{{ route('rewards.index') }}">Rewards</x-nav-link>
                    <x-nav-link icon="activity" :active="request()->routeIs('impact.*')" href="{{ route('impact.index') }}">Impact</x-nav-link>
                    <x-nav-link icon="target" :active="request()->routeIs('missions.*')" href="{{ route('missions.index') }}">Missions</x-nav-link>
                </nav>

                <div class="flex items-center gap-2">
                    <a href="{{ route('wallet.index') }}" class="hidden items-center gap-2 rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-900 transition hover:border-forest-300 sm:flex">
                        <x-icon name="wallet" class="h-3.5 w-3.5 text-forest-600" />
                        <span class="font-mono">₦{{ number_format(auth()->user()->wallet?->cash_balance ?? 0, 0) }}</span>
                        @if ((auth()->user()->wallet?->subsidy_credits ?? 0) > 0)
                            <span class="rounded-full bg-forest-50 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-forest-700">
                                +₦{{ number_format(auth()->user()->wallet->subsidy_credits, 0) }} subsidy
                            </span>
                        @endif
                    </a>

                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-command', { bubbles: true }))"
                            class="hidden items-center gap-1.5 rounded-lg border border-ink-200 bg-white px-2.5 py-1.5 text-xs font-medium text-ink-500 transition hover:border-forest-300 hover:text-ink-700 sm:flex"
                            aria-label="Search (⌘K)">
                        <x-icon name="search" class="h-3.5 w-3.5" />
                        <span class="hidden lg:inline">Search…</span>
                        <kbd class="rounded border border-ink-200 bg-paper px-1 py-0.5 font-mono text-[10px] text-ink-400">⌘K</kbd>
                    </button>

                    <x-profile-menu />
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[480px] px-4 py-6 sm:px-6 lg:max-w-5xl">
            @if (session('status'))
                <x-flash>{{ session('status') }}</x-flash>
            @endif
            @yield('content')
        </main>

        <x-mobile-nav />
        <x-command-palette />

        @yield('scripts')
    </body>
</html>
