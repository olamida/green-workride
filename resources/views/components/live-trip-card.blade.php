{{-- LiveTripCard: stacked ride card with driver, route, live seats, fare, match score --}}
@props([
    'trip' => [],
    'driver' => null,
    'showMatchScore' => true,
    'showJoinButton' => true,
])

@php
    $trip = (object) $trip;
    $driver = $driver ?? $trip->driver ?? null;
    $departs = \Illuminate\Support\Carbon::parse($trip->departure_time);
    $isActive = $trip->status === 'active';
    $isFree = $trip->is_free_volunteer ?? false;
    $isWomenOnly = $trip->women_only ?? false;
    $leavingSoon = $trip->leaving_soon ?? false;
    $bookAhead = $departs->gt(now()->addHour());
@endphp

<a href="{{ route('trips.show', $trip->id) }}" data-trip-card="{{ $trip->id }}"
   class="group block rounded-2xl border border-ink-200 bg-white p-5 transition hover:border-forest-300 hover:shadow-md">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">{{ $trip->corridor_label ?? $trip->corridor }}</span>
                @if ($isFree)
                    <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">Free volunteer</span>
                @endif
                @if ($isWomenOnly)
                    <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Women-only</span>
                @endif
                @if ($isActive)
                    <span class="inline-flex items-center gap-1 rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-forest-500"></span> Live now
                    </span>
                @elseif ($leavingSoon && ! $bookAhead)
                    <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">Leaving soon</span>
                @elseif ($bookAhead)
                    <span class="rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-600">Book ahead</span>
                @endif
            </div>
            <p class="mt-3 font-heading text-lg font-semibold text-ink-900 group-hover:text-forest-700">{{ $trip->route_name }}</p>
            <p class="mt-1 text-sm text-ink-500">{{ $trip->origin_text }} → {{ $trip->destination_text }}</p>
        </div>
        <div class="text-right">
            <p class="font-mono text-lg font-semibold text-ink-900">
                @if ($isFree)
                    Free
                @else
                    ₦{{ number_format((float) $trip->fare_per_seat, 0) }}
                @endif
            </p>
            <p class="text-xs text-ink-500">fixed price</p>
        </div>
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-ink-100 pt-4 text-sm text-ink-600">
        <span>⏰ {{ $departs->format('D, M j · g:i A') }}</span>
        <span>🚌 <span class="font-mono font-semibold text-ink-900" data-seats="{{ $trip->id }}">{{ $trip->available_seats }}/{{ $trip->total_seats }}</span> seats</span>
        @if ($driver)
            <span>👤 {{ $driver['name'] ?? $driver->name ?? '' }}</span>
            @if (($driver['rating_count'] ?? $driver->rating_count ?? 0) > 0)
                <span class="text-gold-600">★ {{ number_format((float) ($driver['rating_avg'] ?? $driver->rating_avg ?? 0), 1) }}</span>
            @endif
        @endif
        @if ($showMatchScore && ($trip->match_score ?? null) !== null)
            <span class="inline-flex items-center rounded-full bg-ink-900 px-2.5 py-0.5 text-xs font-semibold text-white"
                  title="Why this match: {{ implode(' · ', $trip->score_reasons ?? []) }}">
                {{ $trip->match_score }}/100 match
            </span>
        @endif
        @if ($showJoinButton)
            <span class="ml-auto font-semibold text-forest-700 group-hover:underline">View & book →</span>
        @endif
    </div>
</a>