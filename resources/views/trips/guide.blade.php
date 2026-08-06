@extends('layouts.app')

@section('title', 'Connect guide · '.$trip->route_name)

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <a href="{{ route('trips.show', $trip) }}" class="text-sm font-medium text-forest-600 hover:text-forest-700">
            &larr; Back to trip
        </a>

        <div class="mt-4 flex flex-col gap-1">
            <h1 class="font-heading text-2xl font-bold tracking-tight text-ink-900">
                Connect guide
            </h1>
            <p class="text-sm text-ink-600">
                {{ $trip->route_name }} · {{ $trip->corridor->label() }} ·
                departs {{ $trip->departure_time->format('g:i A') }}
            </p>
        </div>

        <div x-data="connectGuideUI()" class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div id="connect-guide-map" x-ref="map"
                     data-config="@json($config)" data-target="@json($target)"
                     class="h-[460px] w-full overflow-hidden rounded-2xl border border-ink-200"
                     style="z-index: 1;" role="region" aria-label="Map of your boarding point"></div>

                {{-- Overview — pin the vehicle, then hand over to the guide. --}}
                <div x-show="mode === 'overview'" x-cloak
                     class="wr-glass wr-scale-in mt-4 rounded-2xl p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Walking distance
                            </p>
                            <p data-guide-distance x-text="distance"
                               class="mt-1 font-mono text-2xl font-semibold text-ink-900">
                                {{ $target['lat'] !== null ? '—' : 'n/a' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                ETA to boarding
                            </p>
                            <p data-guide-eta x-text="eta"
                               class="mt-1 font-mono text-2xl font-semibold text-ink-900">
                                {{ $target['lat'] !== null ? '—' : 'n/a' }}
                            </p>
                        </div>
                    </div>
                    <p data-guide-status x-text="status" aria-live="polite"
                       class="mt-3 text-sm font-medium text-ink-700"></p>
                    @if ($target['lat'] !== null)
                        <button type="button" x-on:click="start()" aria-expanded="false"
                                class="mt-4 min-h-[44px] w-full rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-forest-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-600">
                            Start guide
                        </button>
                    @endif
                </div>

                {{-- Active — compact HUD while walking to the green dot. --}}
                <div x-show="mode === 'active'" x-cloak class="wr-glass mt-4 rounded-2xl p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-forest-500 wr-pulse" aria-hidden="true"></span>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Approaching vehicle
                            </p>
                        </div>
                        <p x-ref="hudDistance" x-text="distance" class="font-mono text-2xl font-semibold text-ink-900"></p>
                        <p x-ref="hudEta" x-text="eta" class="font-mono text-2xl font-semibold text-ink-900"></p>
                    </div>
                    <p data-guide-status x-text="status" aria-live="polite"
                       class="mt-2 text-sm font-medium text-ink-700"></p>
                </div>

                {{-- Arrived — you are here. --}}
                <div x-show="mode === 'arrived'" x-cloak
                     class="wr-glass wr-scale-in mt-4 rounded-2xl border-2 border-forest-500 p-5">
                    <p class="font-heading text-lg font-semibold text-forest-700">
                        You are here — wave to the driver.
                    </p>
                    <p class="mt-1 text-sm text-ink-600">
                        The vehicle is within the pick-up radius. Head to the green dot and let the driver know you have arrived.
                    </p>
                    <a href="{{ route('trips.show', $trip) }}" x-on:click=""
                       class="mt-4 inline-flex min-h-[44px] items-center justify-center rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-forest-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-600">
                        Open trip page
                    </a>
                </div>

                {{-- Missed — the ride is gone; recover gracefully. --}}
                <div x-show="mode === 'missed'" x-cloak class="wr-glass mt-4 rounded-2xl p-5">
                    <p class="font-heading text-lg font-semibold text-ink-700">
                        The ride is gone.
                    </p>
                    <p data-guide-status x-text="missedReason" aria-live="polite"
                       class="mt-1 text-sm text-ink-600"></p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('trips.index') }}"
                           class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-forest-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-600">
                            Find another ride
                        </a>
                        <a href="{{ route('trips.show', $trip) }}"
                           class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-ink-200 bg-white px-4 py-3 text-sm font-semibold text-ink-800 transition-colors hover:bg-ink-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-600">
                            Open trip page
                        </a>
                        @if ($trip->driver->phone)
                            <a href="tel:{{ $trip->driver->phone }}"
                               class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-ink-200 bg-white px-4 py-3 text-sm font-semibold text-ink-800 transition-colors hover:bg-ink-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-600">
                                Call driver
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-ink-200 bg-white p-5">
                    <h2 class="font-heading font-semibold text-ink-900">Meet the ride</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500">Boarding point</dt>
                            <dd class="font-medium text-ink-900">{{ $target['label'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500">Driver</dt>
                            <dd class="font-medium text-ink-900">{{ $trip->driver->name }}</dd>
                        </div>
                        @if ($trip->vehicle)
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-500">Vehicle</dt>
                                <dd class="font-medium text-ink-900">{{ $trip->vehicle->plate_number }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500">Seats left</dt>
                            <dd class="font-medium text-ink-900">{{ $trip->available_seats }} / {{ $trip->total_seats }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gold-200 bg-gold-50/60 p-5">
                    <h2 class="font-heading font-semibold text-ink-900">How the guide works</h2>
                    <ol class="mt-3 space-y-2 text-sm text-ink-700">
                        <li class="flex gap-2"><span class="font-semibold text-forest-600">1.</span> The green dot is where to meet the vehicle.</li>
                        <li class="flex gap-2"><span class="font-semibold text-forest-600">2.</span> Your position is drawn in blue as you move.</li>
                        <li class="flex gap-2"><span class="font-semibold text-forest-600">3.</span> Distance and ETA update live as the vehicle approaches.</li>
                    </ol>
                    <p class="mt-3 text-xs text-ink-500">
                        Your live position is only shown to you and other participants on this ride. It is never broadcast publicly.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/connect-guide.js'])
@endsection
