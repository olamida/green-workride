@extends('layouts.app')

@section('title', 'Where are you going?')

@section('content')
    @php
        $anchors = config('workride.corridor_anchors');
        $cbd = $anchors['cbd'];
        $mapConfig = [
            'fct_bounds' => config('workride.fct_bounds'),
            'corridor_anchors' => $anchors,
            'cbd' => $cbd,
            'min_zoom' => 9,
            'default_zoom' => 12,
        ];
        $mapTrips = collect($trips)->map(fn ($trip) => [
            'id' => $trip['id'],
            'lat' => $trip['current_lat'] && (float) $trip['current_lat'] !== 0.0
                ? (float) $trip['current_lat']
                : ($anchors[$trip['corridor']]['lat'] ?? $cbd['lat']),
            'lng' => $trip['current_lng'] && (float) $trip['current_lng'] !== 0.0
                ? (float) $trip['current_lng']
                : ($anchors[$trip['corridor']]['lng'] ?? $cbd['lng']),
            'route_name' => $trip['route_name'],
            'status' => $trip['status'],
            'is_free_volunteer' => $trip['is_free_volunteer'],
            'leaving_soon' => (bool) ($trip['leaving_soon'] ?? false),
            'departure_time' => \Illuminate\Support\Carbon::parse($trip['departure_time'])->format('D M j · g:i A'),
            'available_seats' => $trip['available_seats'],
            'total_seats' => $trip['total_seats'],
            'fare' => number_format((float) $trip['fare_per_seat'], 0),
            'url' => route('trips.show', $trip['id']),
        ])->values();

        $corridors = collect(\App\Enums\Corridor::cases())->map(fn ($option) => [
            'corridor' => $option->value,
            'label' => $option->label(),
            'live' => ! empty($corridorLive[$option->value]),
            'demand' => $corridorStats[$option->value]['count'] ?? 0,
            'min_fare' => $corridorStats[$option->value]['min_fare'] ?? null,
        ])->values();
    @endphp

    {{-- Alpine data for the Go Board --}}
    <div
        x-data="goBoard()"
        @destination-selected.window="onDestinationSelected($event.detail)"
        @destination-cleared.window="onDestinationCleared()"
        class="h-full flex flex-col"
    >
        {{-- TOP MAP SECTION (55-60%) --}}
        <section
            class="relative flex-1 overflow-hidden"
            :class="{ 'h-[55%]': !mapFocused, 'h-[60%]': mapFocused }"
            aria-label="Map of rides around Abuja"
        >
            {{-- Map Canvas --}}
            <div
                id="go-map"
                class="h-full w-full"
                role="region"
                aria-label="Interactive map showing live rides, demand hotspots, and your location"></div>

            {{-- Floating Search Pill (expands to destination picker) --}}
            <div class="absolute top-4 left-4 right-4 z-20" x-data="whereTo" @destination-selected.window="select($event.detail)">
                <div class="relative max-w-2xl mx-auto">
                    <button
                        type="button"
                        @click="open = true; $refs.input.focus()"
                        :class="[
                            'w-full h-14 rounded-xl bg-white/95 dark:bg-ink-900/95 backdrop-blur-sm shadow-lg border border-ink-200 dark:border-ink-700 px-5 py-3 text-left transition-all',
                            'hover:border-forest-300 hover:shadow-xl',
                            'focus-within:border-forest-500 focus-within:ring-2 focus-within:ring-forest-500/30',
                        ]"
                        aria-label="Where are you going? Tap to search"
                        aria-expanded="false"
                        aria-haspopup="listbox"
                    >
                        <div class="flex items-center gap-3">
                            <x-icon name="map-pin" class="h-5 w-5 text-forest-600 flex-shrink-0" />
                            <span class="flex-1 font-semibold text-ink-900 dark:text-ink-100 truncate" x-text="selected ? selected.name : 'Where are you going?'"></span>
                            <x-icon name="search" class="h-5 w-5 text-ink-400 dark:text-ink-500 flex-shrink-0" />
                        </div>
                    </button>

                    {{-- Live results dropdown --}}
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 transform -translate-y-1"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-1"
                        class="absolute left-0 right-0 z-30 mt-2 max-h-[60vh] overflow-auto rounded-xl border border-ink-200 dark:border-ink-700 bg-white dark:bg-ink-900 shadow-xl"
                        role="listbox"
                        aria-label="Search results"
                    >
                        <div class="p-2 space-y-1">
                            <template x-for="r in results" :key="r.name + r.type + (r.id ?? '')">
                                <button
                                    type="button"
                                    role="option"
                                    @click="select(r)"
                                    class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-3 text-left text-sm transition hover:bg-forest-50 dark:hover:bg-forest-900/20 focus:outline-none focus:ring-2 focus:ring-forest-500/30"
                                >
                                    <span class="min-w-0 flex items-center gap-3">
                                        <span
                                            :class="[
                                                'flex h-8 w-8 items-center justify-center rounded-full',
                                                r.type === 'junction' ? 'bg-forest-100 dark:bg-forest-900/30 text-forest-700 dark:text-forest-400' : 'bg-gold-100 dark:bg-gold-900/30 text-gold-700 dark:text-gold-400',
                                            ]"
                                            aria-hidden="true"
                                        >
                                            <template x-if="r.type === 'junction'">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </template>
                                            <template x-if="r.type !== 'junction'">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </template>
                                        </span>
                                        <span class="flex flex-col">
                                            <span class="block truncate font-semibold text-ink-900 dark:text-ink-100" x-text="r.name"></span>
                                            <span class="block text-xs text-ink-500 dark:text-ink-400 flex items-center gap-1.5">
                                                <span x-text="r.type === 'junction' ? 'Junction' : 'Workplace'"></span>
                                                <template x-if="r.passenger_volume_daily">
                                                    <span class="text-forest-600 dark:text-forest-400">·</span>
                                                    <span class="font-mono" x-text="r.passenger_volume_daily + '+'"></span>
                                                    <span>people surveyed daily</span>
                                                </template>
                                            </span>
                                        </span>
                                    </span>
                                    <span class="shrink-0 rounded-full bg-forest-50 dark:bg-forest-900/30 px-2.5 py-1 text-xs font-semibold text-forest-700 dark:text-forest-400">Go →</span>
                                </button>
                            </template>

                            <template x-if="results.length === 0 && !searching">
                                <div class="px-3 py-4 text-center text-sm text-ink-500 dark:text-ink-400">
                                    No results for "<span x-text="query"></span>"
                                </div>
                            </template>
                        </div>

                        {{-- Quick actions --}}
                        <div class="border-t border-ink-100 dark:border-ink-800 p-2 space-y-1">
                            <a
                                href="{{ route('demand.index') }}"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-forest-700 dark:text-forest-400 hover:bg-forest-50 dark:hover:bg-forest-900/20 transition"
                            >
                                <x-icon name="user-plus" class="h-5 w-5" />
                                <span class="font-semibold">Check in — "I'm here, need a ride"</span>
                            </a>
                            <a
                                href="{{ route('trips.create') }}"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-forest-700 dark:text-forest-400 hover:bg-forest-50 dark:hover:bg-forest-900/20 transition"
                            >
                                <x-icon name="plus" class="h-5 w-5" />
                                <span class="font-semibold">Publish a ride — "I have space"</span>
                            </a>
                        </div>
                    </div>

                    <input
                        x-ref="input"
                        type="text"
                        x-model="query"
                        @input.debounce.250ms="search()"
                        @focus="query.length >= 2 && search()"
                        @keydown.escape="reset()"
                        placeholder="Search junctions, workplaces or places…"
                        class="absolute inset-0 w-full h-full opacity-0 pointer-events-none"
                        aria-label="Search destination"
                    />
                </div>
            </div>

            {{-- Map Legend --}}
            <div class="absolute bottom-4 left-4 right-4 z-10 md:left-auto md:right-4 md:bottom-auto md:top-4 md:w-56">
                <div class="rounded-xl bg-white/95 dark:bg-ink-900/95 backdrop-blur-sm shadow-lg border border-ink-200 dark:border-ink-700 p-3">
                    <p class="text-xs font-semibold text-ink-700 dark:text-ink-300 mb-2">Map Legend</p>
                    <div class="space-y-1.5 text-xs text-ink-600 dark:text-ink-400">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-forest-500"></span> Live ride
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-gold-400"></span> Free volunteer
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-ink-500"></span> Scheduled
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full border-2 border-gold-400 bg-transparent"></span> Demand hotspot
                        </div>
                        <div class="flex items-center gap-2 pt-1 border-t border-ink-100 dark:border-ink-800">
                            <x-icon name="user" class="h-4 w-4 text-forest-600" /> Your location
                        </div>
                    </div>
                </div>
            </div>

            {{-- Current location button --}}
            <button
                type="button"
                @click="locateUser()"
                class="absolute bottom-4 right-4 z-10 md:bottom-auto md:top-20 rounded-xl bg-white/95 dark:bg-ink-900/95 backdrop-blur-sm shadow-lg border border-ink-200 dark:border-ink-700 p-2.5 transition hover:bg-forest-50 dark:hover:bg-forest-900/20 hover:border-forest-300 focus:outline-none focus:ring-2 focus:ring-forest-500/30"
                aria-label="Center map on your location"
            >
                <x-icon name="navigation" class="h-5 w-5 text-forest-600" />
            </button>
        </section>

        {{-- BOTTOM SHEET SECTION (40-45%) --}}
        <section class="relative z-30">
            <x-bottom-sheet id="go-bottom-sheet" title="" :half-height="true">
                <div class="space-y-6">
                    {{-- Route Chips --}}
                    <section aria-label="Routes">
                        <div class="flex flex-wrap gap-3 pb-2" role="list">
                            @foreach ($routeChips as $chip)
                                <x-route-chip
                                    :corridor="$chip['corridor']"
                                    :label="$chip['label']"
                                    :href="route('go', ['corridor' => $chip['corridor']])"
                                    :trip-count="$corridorStats[$chip['corridor']]['count'] ?? 0"
                                    :min-fare="$corridorStats[$chip['corridor']]['min_fare'] ?? null"
                                    :live="$corridorLive[$chip['corridor']] ?? false"
                                    role="listitem"
                                />
                            @endforeach
                        </div>
                    </section>

                    {{-- Window Preset Chips --}}
                    <section aria-label="Departure window">
                        <div class="flex flex-wrap gap-2" role="tablist">
                            @foreach ($windowPresets as $key => $minutes)
                                <button
                                    type="button"
                                    role="tab"
                                    :aria-selected="$key === selectedWindow"
                                    @click="setWindow('{{ $key }}')"
                                    class="inline-flex min-h-[40px] items-center rounded-full border px-3 py-1.5 text-sm font-medium transition
                                        {{ $key === 'now'
                                            ? 'bg-forest-600 text-white border-forest-600'
                                            : 'bg-white dark:bg-ink-900 text-ink-700 dark:text-ink-300 border-ink-200 dark:border-ink-700 hover:border-forest-300 dark:hover:border-forest-700' }}"
                                >
                                    {{ ucfirst($key) }}
                                </button>
                            @endforeach
                        </div>
                    </section>

                    {{-- Live Ride Cards --}}
                    <section aria-label="Available rides">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <h2 class="font-heading text-lg font-bold text-ink-900 dark:text-ink-100">Rides on the board</h2>
                                @if ($demand['people'] > 0)
                                    <p class="mt-0.5 text-sm text-ink-500 dark:text-ink-400">
                                        <strong class="text-forest-700 dark:text-forest-400">{{ $demand['people'] }} people</strong> want a ride right now
                                        @if (count($demand['top_destinations']))
                                            (towards {{ implode(', ', $demand['top_destinations']) }})
                                        @endif — we're matching.
                                    </p>
                                @else
                                    <p class="mt-0.5 text-sm text-ink-500 dark:text-ink-400">Book a seat before the next departure.</p>
                                @endif
                            </div>
                            <a href="{{ route('trips.index') }}" class="text-sm font-semibold text-forest-700 dark:text-forest-400 hover:underline">Full trip board →</a>
                        </div>

                        <div class="space-y-3" x-ref="rideCards">
                            @if (count($trips))
                                @foreach ($trips as $trip)
                                    <x-live-trip-card :trip="$trip" />
                                @endforeach
                            @else
                                <x-empty-state
                                    :demand="$demand"
                                    :hotspots="$hotspots"
                                    :show-be-driver="auth()->user()->canDriveVolunteer()"
                                />
                            @endif
                        </div>
                    </section>
                </div>
            </x-bottom-sheet>
        </section>
    </div>

    @vite(['resources/js/go/board.js', 'resources/js/navigation/search.js'])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Pass map config to JS for destination-cleared handler
            window.__goMapConfig = @json($mapConfig);

            // Initialize the main map
            window.__goMap = window.initGoMap(
                document.getElementById('go-map'),
                @json($mapTrips),
                @json($mapConfig),
                @json($hotspots ?? [])
            );

            // Initialize Reverb live seat updates
            window.initGoBoardLive();
        });
    </script>
@endsection