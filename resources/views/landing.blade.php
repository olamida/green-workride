<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>WorkRide — Staff rides & transit intelligence for Abuja</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-paper">
        <header class="sticky top-0 z-40 border-b border-ink-200/70 bg-white/80 backdrop-blur-lg">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-forest-600 font-heading text-lg font-bold text-white">W</span>
                    <span class="font-heading text-lg font-semibold tracking-tight text-ink-900">Work<span class="text-forest-600">Ride</span></span>
                </a>
                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-forest-600 px-4 py-2 font-medium text-white transition hover:bg-forest-700">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-ink-600 hover:text-ink-900">Sign in</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-forest-600 px-4 py-2 font-medium text-white transition hover:bg-forest-700">
                            Get started
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-4 pt-16 pb-12 lg:pt-24">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-forest-200 bg-forest-50 px-3 py-1 text-xs font-medium text-forest-700">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-forest-500"></span>
                            Abuja's first transit feed · Verified civil servants only
                        </p>
                        <h1 class="font-heading text-4xl font-bold leading-tight tracking-tight text-ink-900 lg:text-5xl">
                            Get to Federal Secretariat by <span class="text-forest-600">7:30am</span>.
                            Every day. <span class="text-gold-500">Verified.</span>
                        </h1>
                        <p class="mt-5 max-w-lg text-lg text-ink-600">
                            With fuel at ₦1,200+/litre, WorkRide moves Abuja workers on fixed-price
                            corridors, volunteer free rides and staff buses — backed by real road data.
                            No surge. No strangers. No guessing.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a href="{{ route('register') }}" class="rounded-xl bg-forest-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-forest-700">
                                Join free
                            </a>
                            <a href="{{ route('login') }}" class="rounded-xl border border-ink-300 px-6 py-3 font-semibold text-ink-700 transition hover:bg-ink-100">
                                I'm a civil servant
                            </a>
                        </div>
                        <p class="mt-6 text-xs text-ink-400">
                            NIN-hashed, never stored · 1,240 verified riders · Works on any phone
                        </p>
                    </div>

                    <div class="space-y-3">
                        <x-matching-anim label="Matching verified civil servants on the Kubwa → CBD corridor…" />

                        @php
                            $corridors = [
                                ['KUBWA', '→ CBD', '₦800', '12 leaving · 6:45am', true],
                                ['NYANYA', '→ IDU', '₦700', '8 leaving · 7:00am', false],
                                ['LUGBE', '→ CBD', '₦600', '5 leaving · 7:15am', false],
                            ];
                        @endphp
                        @foreach ($corridors as [$from, $to, $fare, $meta, $pulse])
                            <div class="flex items-center justify-between rounded-2xl border border-ink-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-forest-600 text-xs font-bold text-white">
                                        {{ $from }}
                                    </span>
                                    <div>
                                        <p class="font-heading font-semibold text-ink-900">{{ $from }} {{ $to }}</p>
                                        <p class="text-xs text-ink-500">{{ $meta }}</p>
                                    </div>
                                </div>
                                <span class="font-mono text-sm font-semibold text-forest-700">{{ $fare }}</span>
                            </div>
                        @endforeach
                        <div class="rounded-2xl border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900">
                            <p class="font-semibold">🚗 Volunteer rides = ₦0</p>
                            <p class="text-xs">Free seats from verified drivers. You earn Green Points.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-ink-200 bg-white">
                <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-3">
                    <div>
                        <h3 class="font-heading font-semibold text-ink-900">Fixed-price corridors</h3>
                        <p class="mt-2 text-sm text-ink-600">Kubwa, Nyanya, Lugbe → CBD. Max ₦800. No surge pricing, ever.</p>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-ink-900">Subsidy-ready</h3>
                        <p class="mt-2 text-sm text-ink-600">MDA-funded wallet credits with a full audit trail for every ride.</p>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-ink-900">Road intelligence</h3>
                        <p class="mt-2 text-sm text-ink-600">Your phone maps potholes and road quality for FERMA. You save fuel.</p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-ink-200 bg-ink-900 py-8 text-center text-xs text-ink-400">
            Built by amateurs, for the working class. From Abuja to the world.
        </footer>
    </body>
</html>
