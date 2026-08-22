<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="user-id" content="{{ auth()->id() }}">
        <meta name="theme-color" content="#1B5E20">
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
    <body class="min-h-screen bg-paper pb-safe lg:pb-0" x-data="darkMode()" x-init="init()">
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

        {{-- Top bar — minimal, translucent over map when full-bleed --}}
        <header class="sticky top-0 z-40 h-16 border-b border-ink-200/70 bg-white/80 dark:bg-ink-950/80 backdrop-blur-lg pt-safe">
            <div class="mx-auto flex max-w-[480px] items-center justify-between gap-4 px-4 sm:px-6 lg:max-w-5xl">
                <a href="{{ route('go') }}" class="flex shrink-0 items-center gap-2" aria-label="WorkRide Home">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-forest-600 font-heading text-base font-bold text-white">W</span>
                    <span class="font-heading text-base font-semibold tracking-tight text-ink-900 dark:text-ink-100">Work<span class="text-forest-600">Ride</span></span>
                </a>

                {{-- Search / Destination pill --}}
                <div class="flex-1 max-w-lg hidden sm:block">
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-command', { bubbles: true }))"
                            class="w-full flex items-center gap-2 rounded-xl border border-ink-200 dark:border-ink-700 bg-white dark:bg-ink-900 px-4 py-2 text-sm text-ink-500 dark:text-ink-400 transition hover:border-forest-300 hover:text-ink-700 dark:hover:text-ink-200 focus:ring-2 focus:ring-forest-500 focus:border-forest-500"
                            aria-label="Where are you going? Search destination">
                        <x-icon name="map-pin" class="h-4 w-4 text-forest-600" />
                        <span>Where are you going?</span>
                        <kbd class="rounded border border-ink-200 dark:border-ink-700 bg-paper dark:bg-ink-800 px-1.5 py-0.5 font-mono text-[10px] text-ink-400 dark:text-ink-500">⌘K</kbd>
                    </button>
                </div>

                {{-- Right actions --}}
                <div class="flex items-center gap-2">
                    {{-- Wallet pill --}}
                    <a href="{{ route('wallet.index') }}"
                       class="hidden items-center gap-2 rounded-full border border-ink-200 dark:border-ink-700 bg-white dark:bg-ink-900 px-3 py-1.5 text-xs font-semibold text-ink-900 dark:text-ink-100 transition hover:border-forest-300 sm:flex"
                       aria-label="Wallet balance">
                        <x-icon name="wallet" class="h-3.5 w-3.5 text-forest-600" />
                        <span class="font-mono">₦{{ number_format(auth()->user()->wallet?->cash_balance ?? 0, 0) }}</span>
                        @if ((auth()->user()->wallet?->subsidy_credits ?? 0) > 0)
                            <span class="rounded-full bg-forest-50 dark:bg-forest-900/30 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-forest-700 dark:text-forest-400"
                                  aria-label="Office support credits">
                                +₦{{ number_format(auth()->user()->wallet->subsidy_credits, 0) }}
                            </span>
                        @endif
                    </a>

                    {{-- Search button mobile --}}
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-command', { bubbles: true }))"
                            class="flex items-center gap-1.5 rounded-lg border border-ink-200 dark:border-ink-700 bg-white dark:bg-ink-900 px-2.5 py-1.5 text-xs font-medium text-ink-500 dark:text-ink-400 transition hover:border-forest-300 hover:text-ink-700 dark:hover:text-ink-200 sm:hidden"
                            aria-label="Search (⌘K)">
                        <x-icon name="search" class="h-3.5 w-3.5" />
                    </button>

                    {{-- Profile menu --}}
                    <x-profile-menu />
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[480px] px-4 py-4 sm:px-6 lg:max-w-5xl">
            @if (session('status'))
                <x-flash>{{ session('status') }}</x-flash>
            @endif
            @yield('content')
        </main>

        {{-- Global bottom tab bar --}}
        <x-mobile-bottom-tab-bar />

        {{-- FAB for "Add My Vehicle" on Go screen --}}
        @if (request()->routeIs('go') && auth()->user()->canBookBenefits())
            <x-fab-add-vehicle />
        @endif

        {{-- Command palette --}}
        <x-command-palette />

        @yield('scripts')
    </body>
</html>