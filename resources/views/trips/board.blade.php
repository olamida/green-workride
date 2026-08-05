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
        <ol class="mt-3 grid gap-3 text-sm text-ink-700 sm:grid-cols-3">
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 font-mono text-xs font-bold text-white">1</span>
                <span><strong>Pick your corridor</strong> — tap Kubwa, Nyanya or Lugbe above. "All corridors" shows every trip.</span>
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

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'window' => $window === 'any' ? null : $window])) }}" @class([
            'rounded-full px-4 py-2 text-sm font-semibold transition',
            'bg-ink-900 text-white' => ! $corridor,
            'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => $corridor,
        ])>All corridors</a>
        @foreach (\App\Enums\Corridor::cases() as $option)
            <a href="{{ route('trips.index', array_filter(['corridor' => $option->value, 'window' => $window])) }}" @class([
                'rounded-full px-4 py-2 text-sm font-semibold transition',
                'bg-ink-900 text-white' => $corridor?->value === $option->value,
                'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => $corridor?->value !== $option->value,
            ])>
                <span class="mr-1 inline-block h-2 w-2 animate-pulse rounded-full bg-forest-500"></span>
                {{ $option->label() }}
            </a>
        @endforeach
        <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'women_only' => $womenOnly ? null : '1', 'window' => $window])) }}" @class([
            'rounded-full px-4 py-2 text-sm font-semibold transition',
            'bg-rose-600 text-white' => $womenOnly,
            'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => ! $womenOnly,
        ])>
            ♀ Women-only
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
                    Leaving soon
                @elseif ($key === 'later')
                    Later today
                @elseif ($key === 'tomorrow')
                    Tomorrow
                @else
                    Anytime (48h)
                @endif
            </a>
        @endforeach
        @if ($trips->isNotEmpty())
            <span class="ml-auto text-xs font-medium text-ink-400">
                {{ $trips->count() }} trip{{ $trips->count() === 1 ? '' : 's' }} available
            </span>
        @endif
    </div>

    <div class="space-y-4">
        @forelse ($trips as $trip)
            <a href="{{ route('trips.show', $trip) }}" class="group block rounded-2xl border border-ink-200 bg-white p-5 transition hover:border-forest-300 hover:shadow-md">
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
                    <span>🚌 {{ $trip->available_seats }}/{{ $trip->total_seats }} seats</span>
                    <span>👤 {{ $trip->driver?->name }}</span>
                    @if ($trip->driver_rating_count)
                        <span class="text-gold-600">★ {{ number_format((float) $trip->driver_rating_avg, 1) }} ({{ $trip->driver_rating_count }})</span>
                    @endif
                    <span class="ml-auto font-semibold text-forest-700 group-hover:underline">View &amp; book →</span>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-ink-200 bg-white px-6 py-10 text-center">
                <p class="font-heading text-lg font-semibold text-ink-900">No trips in this window yet</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-ink-500">
                    @if ($window === 'now')
                        Nothing is leaving in the next 30 minutes. Try <a href="{{ route('trips.index', array_filter(['corridor' => $corridor?->value, 'window' => 'any'])) }}" class="font-semibold text-forest-600 hover:underline">Anytime (48h)</a> to plan a seat a day ahead.
                    @else
                        The matcher keeps scanning. Check another corridor, widen the time window above, or be the first to publish on this route.
                    @endif
                </p>
                @if (auth()->user()->canDriveVolunteer())
                    <a href="{{ route('trips.create') }}" class="mt-4 inline-block rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Publish a trip
                    </a>
                @endif
            </div>
        @endforelse
    </div>
@endsection
