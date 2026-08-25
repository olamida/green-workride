@extends('layouts.app')

@section('title', 'Trip Board')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-ink-900">Trip Board</h1>
<p class="mt-1 text-sm text-ink-500">
    Verified colleagues riding together, fixed price, no surge.
</p>
        </div>
        @if (auth()->user()->canDriveVolunteer())
            <a href="{{ route('trips.create') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                + Publish a trip
            </a>
        @endif
    </div>

    {{-- How to book — first-time riders shouldn't have to guess. --}}
    <div class="mb-6 rounded-2xl border border-forest-200 bg-forest-50/60 p-5">
        <p class="font-heading text-sm font-semibold text-forest-900">How to book a seat</p>
        @if ($nextTrip)
            <p class="mt-1 text-sm text-forest-800">
                <strong>Motor go come out:</strong>
                {{ $nextTrip->route_name }} for {{ $nextTrip->departure_time->format('g:i A') }}
                — {{ $nextTrip->available_seats }}/{{ $nextTrip->total_seats }} seats remain.
            </p>
        @elseif ($demandSnapshot['people'] > 0)
            <p class="mt-1 text-sm text-forest-800">
                <strong>{{ $demandSnapshot['people'] }} people</strong> don wait for motor right now
                @if (count($demandSnapshot['top_destinations']))
                    (towards {{ implode(', ', $demandSnapshot['top_destinations']) }})
                @endif — we’re matching. <a href="{{ route('demand.index') }}" class="font-semibold text-forest-700 underline hover:text-forest-900">Check in</a> so Ops knows where send bus.
            </p>
        @elseif (count($hotspots))
            <p class="mt-1 text-sm text-forest-800">
                <strong>Live demand at {{ collect($hotspots)->take(3)->implode('name', ' · ') }}</strong>
                @if (auth()->user()->canDriveVolunteer())
                    — <a href="{{ route('trips.create', ['corridor' => $hotspots[0]['corridor'] ?? '']) }}" class="font-semibold text-forest-700 underline hover:text-forest-900">Publish motor</a> for people wey need am.
                @else
                    — we’re matching a driver.
                @endif
            </p>
        @endif
<ol class="mt-3 grid gap-3 text-sm text-ink-700 sm:grid-cols-3">
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 font-mono text-xs font-bold text-white">1</span>
                <span><strong>Pick your route</strong> — tap Kubwa, Nyanya or Lugbe above. "All routes" shows every motor.</span>
            </li>
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 font-mono text-xs font-bold text-white">2</span>
                <span><strong>Tap a trip card</strong> — it opens the trip page with the driver, vehicle, stops and live seats.</span>
            </li>
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 font-mono text-xs font-bold text-white">3</span>
                <span><strong>Book a seat</strong> — choose wallet, subsidy, cash or ride-credit and confirm. The driver is notified instantly.</span>
            </li>
        </ol>
    </div>

    @if (config('workride.driver_prompts.enabled') && auth()->user()->canDriveVolunteer() && $driverPrompts->isNotEmpty())
        @php
            $openPrompts = $driverPrompts->filter(fn ($prompt) => $prompt->status === \App\Enums\DriverPromptStatus::Prompted)->take(2);
        @endphp
        @if ($openPrompts->isNotEmpty())
            <div class="mb-6 rounded-2xl border border-gold-300 bg-gold-50/70 p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-heading text-sm font-semibold text-ink-900">Demand wants you</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-100 px-2.5 py-1 text-xs font-semibold text-gold-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-gold-600 wr-pulse"></span> live signal
                    </span>
                </div>
                <div class="mt-3 space-y-3">
                    @foreach ($openPrompts as $prompt)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gold-200 bg-white px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink-900">
                                    {{ $prompt->corridor?->label() }}
                                    <span class="ml-1 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-600">
                                        {{ $prompt->people_count }} people waiting
                                    </span>
                                </p>
                                <p class="mt-0.5 text-xs text-ink-500">
                                    Commuters checked in around {{ $prompt->corridor?->short() }} in the last few hours — supply is short.
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <form method="POST" action="{{ route('prompts.accept', $prompt) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-forest-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-forest-700">
                                        Publish on this corridor →
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('prompts.dismiss', $prompt) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="rounded-xl px-3 py-2 text-xs font-medium text-ink-500 transition hover:bg-ink-100">
                                        Not today
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'window' => $window === 'any' ? null : $window])) }}" @class([
            'rounded-full px-4 py-2 text-sm font-semibold transition',
            'bg-ink-900 text-white' => ! $corridor,
            'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => $corridor,
        ])>All corridors</a>
        @foreach (\App\Enums\Corridor::cases() as $option)
            @php
                $isLive = ! empty($corridorLive[$option->value]);
                $stats = $corridorStats[$option->value] ?? null;
            @endphp
            <a href="{{ route('trips.index', array_filter(['corridor' => $option->value, 'window' => $window])) }}" @class([
                'inline-flex min-h-[44px] items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold transition',
                'bg-ink-900 text-white' => $corridor?->value === $option->value,
                'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => $corridor?->value !== $option->value,
            ]) @if ($isLive) data-corridor-chip="{{ $option->value }}" @endif>
                <span @class([
                    'inline-block h-2 w-2 rounded-full bg-forest-500',
                    'wr-pulse' => $isLive,
                    'opacity-40' => ! $isLive,
                ]) aria-hidden="true"></span>
                {{ $option->label() }}
                @if ($stats && $stats['count'] > 0)
                    <span class="font-mono text-xs opacity-80">
                        · {{ $stats['count'] }}
                        @if ($stats['min_fare'] !== null)
                            · {{ $stats['min_fare'] === 0 ? 'FREE' : '₦'.number_format($stats['min_fare']) }}
                        @endif
                    </span>
                @endif
                @if ($isLive)
                    <span class="sr-only"> — live trips leaving soon</span>
                @endif
            </a>
        @endforeach
        <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'women_only' => $womenOnly ? null : '1', 'window' => $window])) }}" @class([
            'rounded-full px-4 py-2 text-sm font-semibold transition',
            'bg-rose-600 text-white' => $womenOnly,
            'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => ! $womenOnly,
        ])>
            ♀ Women-only Motor wey na only for women
        </a>
    </div>

    {{-- Departure window — "leaving soon" vs "planning ahead". --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($presets as $key => $minutes)
            <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'window' => $key, 'women_only' => $womenOnly ? '1' : null])) }}" @class([
                'rounded-full px-3.5 py-1.5 text-xs font-semibold transition',
                'bg-forest-600 text-white' => $window === $key,
                'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => $window !== $key,
            ])>
                @if ($key === 'now')
                    Motor go come soon
                @elseif ($key === 'later')
                    E go come later
                @elseif ($key === 'tomorrow')
                    Tomorrow — make we plan am
                @else
                    Anytime (48h) — plan am seat ahead
                @endif
            </a>
        @endforeach
        @if ($trips->isNotEmpty())
            <span class="ml-auto text-xs font-medium text-ink-400">
                {{ $trips->count() }} trip{{ $trips->count() === 1 ? '' : 's' }} available
            </span>
        @endif
    </div>

    {{-- Guaranteed next departures — the recurring timetable materialised ahead. --}}
    @if (! empty($nextDepartures))
        <div class="mb-6 rounded-2xl border border-forest-200 bg-forest-50 px-5 py-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-heading text-sm font-semibold text-ink-900">Next departures</h2>
                <span class="text-xs text-ink-500">Guaranteed recurring slots</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($nextDepartures as $departure)
                    @if ($departure['source'] === 'trip' && $departure['trip_id'])
                        <a href="{{ route('trips.show', $departure['trip_id']) }}" class="group flex items-center gap-2 rounded-xl border border-forest-200 bg-white px-3 py-2 text-xs font-semibold text-ink-900 transition hover:border-forest-400">
                            <span class="font-mono">{{ $departure['departure_time']->format('D g:i A') }}</span>
                            <span class="text-ink-500">{{ $departure['corridor']->label() }}</span>
                            <span class="font-mono text-forest-700">₦{{ number_format((float) $departure['fare']) }}</span>
                            <span class="text-ink-400 group-hover:text-ink-600">→</span>
                        </a>
                    @else
                        <span class="flex items-center gap-2 rounded-xl border border-dashed border-forest-300 bg-white px-3 py-2 text-xs font-medium text-ink-700">
                            <span class="font-mono">{{ $departure['departure_time']->format('D g:i A') }}</span>
                            <span class="text-ink-500">{{ $departure['corridor']->label() }}</span>
                            <span class="font-mono text-forest-700">₦{{ number_format((float) $departure['fare']) }}</span>
                            <span class="text-[10px] uppercase tracking-wide text-ink-400">scheduled</span>
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <div class="space-y-4" x-data="boardLive">
        @php
            $anchors = config('workride.corridor_anchors');
            $cbd = $anchors['cbd'];
            $mapTrips = $trips->map(fn ($trip) => [
                'id' => $trip->id,
                'lat' => $trip->current_lat && (float) $trip->current_lat !== 0.0
                    ? (float) $trip->current_lat
                    : ($anchors[$trip->corridor->value]['lat'] ?? $cbd['lat']),
                'lng' => $trip->current_lng && (float) $trip->current_lng !== 0.0
                    ? (float) $trip->current_lng
                    : ($anchors[$trip->corridor->value]['lng'] ?? $cbd['lng']),
                'route_name' => $trip->route_name,
                'status' => $trip->status->value,
                'is_free_volunteer' => $trip->is_free_volunteer,
                'leaving_soon' => (bool) ($trip->leaving_soon ?? false),
                'departure_time' => $trip->departure_time->format('D M j · g:i A'),
                'available_seats' => $trip->available_seats,
                'total_seats' => $trip->total_seats,
                'fare' => number_format((float) $trip->fare_per_seat, 0),
                'url' => route('trips.show', $trip),
            ])->values();
        @endphp

        @if ($mapTrips->isNotEmpty())
            <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                    <h2 class="font-heading text-sm font-semibold text-ink-900">Map view</h2>
                    <p class="text-xs text-ink-500">Green = live · Gold = free volunteer · Slate = scheduled</p>
                </div>
                <div id="trips-map" class="h-[380px] w-full" role="region"
                     aria-label="Map of available trips. Use the trip list below to view details and book a seat."></div>
            </div>
            @vite(['resources/js/trips-map.js'])
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    window.__tripsMap = window.initTripsMap(
                        document.getElementById('trips-map'),
                        @json($mapTrips),
                        { cbd: @json($cbd) }
                    );
                });
            </script>
        @endif

        @forelse ($trips as $trip)
            <a href="{{ route('trips.show', $trip) }}" data-trip-card="{{ $trip->id }}" class="group block rounded-2xl border border-ink-200 bg-white p-5 transition hover:border-forest-300 hover:shadow-md">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">{{ $trip->corridor->short() }}</span>
                            @if ($trip->is_free_volunteer)
                                <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">FREE volunteer</span>
                            @endif
                            @if ($trip->women_only)
                                <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Women-only</span>
                            @endif
                            @if ($trip->status->value === 'active')
                                <span class="inline-flex items-center gap-1 rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-forest-500"></span> Live now
                                </span>
                            @elseif (($trip->leaving_soon ?? false) && ! $trip->departure_time->gt(now()->addHour()))
                                <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">Leaving soon</span>
                            @elseif ($trip->departure_time->gt(now()->addHour()))
                                <span class="rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-600">Book ahead</span>
                            @endif
                        </div>
                        <p class="mt-3 font-heading text-lg font-semibold text-ink-900 group-hover:text-forest-700">{{ $trip->route_name }}</p>
                        <p class="mt-1 text-sm text-ink-500">
                            {{ $trip->origin_text }} → {{ $trip->destination_text }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="font-mono text-lg font-semibold text-ink-900">₦{{ number_format((float) $trip->fare_per_seat, 0) }}</p>
                        <p class="text-xs text-ink-500">fixed price</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-ink-100 pt-4 text-sm text-ink-600">
                    <span>⏰ {{ $trip->departure_time->format('D, M j · g:i A') }}</span>
                    <span>🚌 <span data-seats data-corridor="{{ $trip->corridor->value }}" aria-live="polite" class="font-mono font-semibold text-ink-900">{{ $trip->available_seats }}/{{ $trip->total_seats }}</span> seats</span>
                    <span data-seats-full class="{{ $trip->available_seats > 0 ? 'hidden' : '' }} rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-600">Full</span>
                    <span>👤 {{ $trip->driver?->name }}</span>
                    @if ($trip->driver_rating_count)
                        <span class="text-gold-600">★ {{ number_format((float) $trip->driver_rating_avg, 1) }} ({{ $trip->driver_rating_count }})</span>
                    @endif
                    @if ($trip->driver_score)
                        <span class="inline-flex items-center rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700" title="{{ $trip->driver_score->level->label() }} driver · {{ $trip->driver_score->rides_completed }} rides this week">
                            {{ $trip->driver_score->score }} · {{ $trip->driver_score->level->label() }}
                        </span>
                    @endif
                    @if (($trip->match_score ?? null) !== null)
                        <span class="inline-flex items-center rounded-full bg-ink-900 px-2.5 py-0.5 text-xs font-semibold text-white" title="Why this match: {{ implode(' · ', $trip->score_reasons ?? []) }}">
                            {{ $trip->match_score }}/100 match
                        </span>
                        @if (count($trip->score_reasons ?? []))
                            <span class="text-xs text-ink-400">
                                {{ implode(' · ', array_slice($trip->score_reasons, 0, 3)) }}
                            </span>
                        @endif
                    @endif
                    <span data-book-link class="ml-auto font-semibold text-forest-700 group-hover:underline">View &amp; book →</span>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-ink-200 bg-white px-6 py-10 text-center">
                <p class="font-heading text-lg font-semibold text-ink-900">
                    @if ($demandSnapshot['people'] > 0)
                        {{ $demandSnapshot['people'] }} people don wait for motor — You get motor? Help people!
                    @else
                        No motor dey go for this window yet — you get motor? Help people!
                    @endif
                </p>
                <p class="mx-auto mt-1 max-w-md text-sm text-ink-500">
                    @if ($demandSnapshot['people'] > 0)
                        @if (count($demandSnapshot['top_destinations']))
                            Demand is live for <strong>{{ implode(', ', $demandSnapshot['top_destinations']) }}</strong> — a driver is being matched.
                        @else
                            Demand is live and a driver is being matched.
                        @endif
                        <a href="{{ route('demand.index') }}" class="font-semibold text-forest-600 hover:underline">Check in at your junction</a> to strengthen the signal, or
                        @if ($window === 'now')
                            <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'window' => 'any'])) }}" class="font-semibold text-forest-600 hover:underline">plan ahead in the next 48h</a>.
                        @else
                            try another corridor or widen the time window above.
                        @endif
                    @elseif (count($hotspots))
                        Verified drivers can turn this demand into rides.
                    @else
                        @if ($window === 'now')
                            Nothing is leaving in the next 30 minutes. Try <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'window' => 'any'])) }}" class="font-semibold text-forest-600 hover:underline">Anytime (48h)</a> to plan a seat a day ahead.
                        @else
                            The matcher keeps scanning. Check another corridor, widen the time window above, or be the first to publish on this route.
                        @endif
                    @endif
                </p>

                @if (count($hotspots))
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
                                        Publish motor →
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                    @if (auth()->user()->canDriveVolunteer())
                        <a href="{{ route('trips.create') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                            Add My Motor — I Get Space
                        </a>
                    @endif
                    <a href="{{ route('demand.index') }}" class="rounded-xl border border-forest-600 px-4 py-2 text-sm font-semibold text-forest-700 transition hover:bg-forest-50">
                        I need a ride
                    </a>
                </div>
            </div>
        @endforelse
    </div>
@endsection
