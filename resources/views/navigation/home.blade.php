@extends('layouts.app')

@section('title', 'Where to?')

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
        ])->values();
    @endphp

    <div x-data="whereTo" @destination-cleared.window="map && map.focusDestination(null)" class="space-y-6">
        {{-- Hero: "Where are you going?" --}}
        <section class="rounded-2xl border border-forest-200 bg-forest-50/60 p-6">
            <h1 class="font-heading text-2xl font-bold text-ink-900">Where are you going?</h1>
            <p class="mt-1 text-sm text-ink-500">
                Tell us your destination, then book the ride going your way — fixed price, verified colleagues, no surge.
            </p>

            <div class="relative mt-4 max-w-xl">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                <input
                    type="text"
                    x-model="query"
                    @input.debounce.250ms="search()"
                    @focus="query.length >= 2 && search()"
                    @keydown.escape="reset()"
                    placeholder="Search junctions, workplaces or places…"
                    class="h-12 w-full rounded-xl border border-ink-200 bg-white pl-11 pr-4 text-sm text-ink-900 placeholder:text-ink-400 focus:border-forest-500 focus:ring-2 focus:ring-forest-500/30 focus:outline-none"
                    aria-label="Search for a destination" />

                <span x-show="searching" x-cloak class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-forest-600">…</span>

                {{-- Live results --}}
                <ul x-show="open" x-cloak class="absolute left-0 right-0 z-30 mt-2 max-h-72 overflow-auto rounded-xl border border-ink-200 bg-white p-2 shadow-lg">
                    <template x-for="r in results" :key="r.name + r.type">
                        <li>
                            <button
                                type="button"
                                @click="select(r)"
                                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition hover:bg-forest-50">
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-ink-900" x-text="r.name"></span>
                                    <span class="block text-xs text-ink-500">
                                        <span x-text="r.type"></span>
                                        <template x-if="r.passenger_volume_daily">
                                            <span> · <span x-text="r.passenger_volume_daily"></span>+ people surveyed daily</span>
                                        </template>
                                    </span>
                                </span>
                                <span class="shrink-0 rounded-full bg-forest-50 px-2.5 py-1 text-xs font-semibold text-forest-700">Go →</span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </section>

        {{-- Live corridor chips --}}
        <section aria-label="Corridors">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('trips.index') }}" class="inline-flex min-h-[44px] items-center rounded-full bg-ink-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-ink-800">
                    All corridors
                </a>
                @foreach ($corridors as $chip)
                    <a
                        href="{{ route('trips.index', ['corridor' => $chip['corridor']]) }}"
                        data-corridor-chip="{{ $chip['corridor'] }}"
                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-forest-300 hover:bg-forest-50">
                        <span @class([
                            'inline-block h-2 w-2 rounded-full bg-forest-500',
                            'wr-pulse' => $chip['live'],
                            'opacity-40' => ! $chip['live'],
                        ]) aria-hidden="true"></span>
                        {{ $chip['label'] }}
                        @if ($chip['demand'] > 0)
                            <span class="font-mono text-xs opacity-80">· {{ $chip['demand'] }}</span>
                        @endif
                        @if ($chip['live'])
                            <span class="sr-only"> — live trips leaving soon</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Never-empty map canvas --}}
        <section aria-label="Map of trips around Abuja" class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                <h2 class="font-heading text-sm font-semibold text-ink-900">Around Abuja</h2>
                <p class="text-xs text-ink-500">Green = live · Gold = free volunteer · Slate = scheduled</p>
            </div>
            <div
                id="navigation-map"
                class="h-[380px] w-full"
                role="region"
                aria-label="Map of available trips. Use the search above or the list below to book a seat."></div>
        </section>

        {{-- Bottom sheet: rides going your way --}}
        <section aria-label="Available rides">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-heading text-lg font-bold text-ink-900">Rides on the board</h2>
                    @if ($demand['people'] > 0)
                        <p class="mt-0.5 text-sm text-ink-500">
                            <strong class="text-forest-700">{{ $demand['people'] }} people</strong> want a ride right now
                            @if (count($demand['top_destinations']))
                                (towards {{ implode(', ', $demand['top_destinations']) }})
                            @endif — we’re matching.
                        </p>
                    @else
                        <p class="mt-0.5 text-sm text-ink-500">Book a seat before the next departure.</p>
                    @endif
                </div>
                <a href="{{ route('trips.index') }}" class="text-sm font-semibold text-forest-700 hover:underline">Full trip board →</a>
            </div>

            @if (count($trips))
                <div class="mt-4 space-y-3">
                    @foreach ($trips as $trip)
                        @php
                            $driver = $trip['driver'] ?? null;
                            $departs = \Illuminate\Support\Carbon::parse($trip['departure_time']);
                        @endphp
                        <a href="{{ route('trips.show', $trip['id']) }}" data-trip-card="{{ $trip['id'] }}"
                           class="group block rounded-2xl border border-ink-200 bg-white p-5 transition hover:border-forest-300 hover:shadow-md">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">{{ $trip['corridor_label'] }}</span>
                                        @if ($trip['is_free_volunteer'])
                                            <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">FREE volunteer</span>
                                        @endif
                                        @if ($trip['women_only'])
                                            <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Women-only</span>
                                        @endif
                                        @if ($trip['status'] === 'active')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-forest-500"></span> Live now
                                            </span>
                                        @elseif (($trip['leaving_soon'] ?? false) && ! $departs->gt(now()->addHour()))
                                            <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">Leaving soon</span>
                                        @elseif ($departs->gt(now()->addHour()))
                                            <span class="rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-600">Book ahead</span>
                                        @endif
                                    </div>
                                    <p class="mt-3 font-heading text-lg font-semibold text-ink-900 group-hover:text-forest-700">{{ $trip['route_name'] }}</p>
                                    <p class="mt-1 text-sm text-ink-500">{{ $trip['origin_text'] }} → {{ $trip['destination_text'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-lg font-semibold text-ink-900">
                                        @if ($trip['is_free_volunteer'])
                                            FREE
                                        @else
                                            ₦{{ number_format((float) $trip['fare_per_seat'], 0) }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-ink-500">fixed price</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-ink-100 pt-4 text-sm text-ink-600">
                                <span>⏰ {{ $departs->format('D, M j · g:i A') }}</span>
                                <span>🚌 <span class="font-mono font-semibold text-ink-900">{{ $trip['available_seats'] }}/{{ $trip['total_seats'] }}</span> seats</span>
                                @if ($driver)
                                    <span>👤 {{ $driver['name'] }}</span>
                                    @if (($driver['rating_count'] ?? 0) > 0)
                                        <span class="text-gold-600">★ {{ number_format((float) $driver['rating_avg'], 1) }}</span>
                                    @endif
                                @endif
                                <span class="ml-auto font-semibold text-forest-700 group-hover:underline">View &amp; book →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="mt-4 rounded-2xl border border-ink-200 bg-white px-6 py-10 text-center">
                    <p class="font-heading text-lg font-semibold text-ink-900">
                        @if ($demand['people'] > 0)
                            {{ $demand['people'] }} people want this journey
                        @else
                            No trips on the board yet
                        @endif
                    </p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-ink-500">
                        @if ($demand['people'] > 0)
                            @if (count($demand['top_destinations']))
                                Demand is live for <strong>{{ implode(', ', $demand['top_destinations']) }}</strong> — a driver is being matched.
                            @else
                                Demand is live and a driver is being matched.
                            @endif
                            <a href="{{ route('demand.index') }}" class="font-semibold text-forest-600 hover:underline">Check in at your junction</a> to strengthen the signal.
                        @else
                            The matcher keeps scanning. Check another corridor, or be the first to publish on this route.
                        @endif
                    </p>
                    @if (count($hotspots ?? []))
                        <ul class="mx-auto mt-4 max-w-md space-y-2 text-left">
                            @foreach (collect($hotspots)->take(3) as $hotspot)
                                <li class="flex items-center justify-between gap-3 rounded-xl border border-ink-100 bg-forest-50/40 px-4 py-2.5 text-sm">
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-ink-900">{{ $hotspot['name'] }}</span>
                                        <span class="block text-xs text-ink-500">
                                            {{ $hotspot['people'] }} people waiting
                                            @if ($hotspot['corridor'])
                                                · {{ $hotspot['corridor'] }}
                                            @endif
                                        </span>
                                    </span>
                                    @if (auth()->user()->canDriveVolunteer())
                                        <a href="{{ route('trips.create', ['corridor' => $hotspot['corridor'] ?? '']) }}" class="shrink-0 rounded-full bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">
                                            Be the driver →
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                        @if (auth()->user()->canDriveVolunteer())
                            <a href="{{ route('trips.create') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">Publish a trip</a>
                        @endif
                        <a href="{{ route('demand.index') }}" class="rounded-xl border border-forest-600 px-4 py-2 text-sm font-semibold text-forest-700 transition hover:bg-forest-50">I need a ride</a>
                    </div>
                </div>
            @endif
        </section>
    </div>

    @vite(['resources/js/navigation/search.js', 'resources/js/navigation/navigation.js'])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.__navMap = window.initNavigationMap(
                document.getElementById('navigation-map'),
                @json($mapTrips),
                @json($mapConfig)
            );
            window.__navCorridors = @json($corridors);

            window.addEventListener('destination-selected', (event) => {
                window.__navMap?.focusDestination(event.detail);
            });
            window.addEventListener('destination-cleared', () => {
                if (window.__navMap?.map) {
                    const config = @json($mapConfig);
                    window.__navMap.map.setView([config.cbd.lat, config.cbd.lng], config.default_zoom);
                }
            });
        });
    </script>
@endsection
