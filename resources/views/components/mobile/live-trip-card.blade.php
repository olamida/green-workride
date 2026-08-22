{{-- LiveTripCard — driver avatar + verification badge + seats + score reasons + IRI indicator + primary action --}}
@props([
    'trip' => null,              // Trip model
    'matchScore' => null,        // int 0-100
    'scoreReasons' => [],        // array of reason strings
    'showSoftHold' => false,     // feature-gated soft hold
    'onBook' => null,            // wire:click or onclick handler
    'onSoftHold' => null,        // wire:click or onclick handler
])

<?php
$trip = $trip;
$driver = $trip->driver;
$vehicle = $trip->vehicle;
$isFree = $trip->is_free_volunteer;
$seatsLeft = $trip->available_seats;
$totalSeats = $trip->total_seats;
$isLive = $trip->status->value === 'active';
$isLeavingSoon = $trip->departure_time && $trip->departure_time->diffInMinutes(now()) <= 15 && !$isLive;
$iriCondition = $trip->current_iri_condition ?? 'good';
$iriColors = [
    'excellent' => 'bg-forest-500',
    'good' => 'bg-forest-400',
    'fair' => 'bg-gold-400',
    'poor' => 'bg-red-500',
];
$iriColor = $iriColors[$iriCondition] ?? 'bg-forest-400';
$origin = $trip->origin_text ?? 'Unknown';
$destination = $trip->destination_text ?? 'Unknown';
$waypointLabels = $trip->waypoints->pluck('label')->implode(' → ');
$routeTitle = $waypointLabels ?: "{$origin} → {$destination}";
$departureTime = $trip->departure_time?->format('g:i A') ?? '—';
$fare = $isFree ? 'Free' : '₦' . number_format($trip->fare_per_seat ?? 0, 0);
?>

<div class="wr-card wr-transition-normal overflow-hidden" data-trip-card data-trip-id="{{ $trip->id }}">
    {{-- Driver header --}}
    <div class="flex items-center gap-3 p-4 border-b border-ink-100">
        <div class="relative flex-shrink-0">
            <img src="{{ $driver->avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($driver->name) . '&background=1B5E20&color=fff&size=48' }}"
                 alt="" class="w-12 h-12 rounded-full object-cover ring-2 ring-white">
            {{-- Verification badge --}}
            @if ($driver->verification_level->value >= 3)
                <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-forest-600 flex items-center justify-center ring-2 ring-white"
                      aria-label="Verified driver">
                    <x-icon name="shield-check" class="h-3.5 w-3.5 text-white" />
                </span>
            @elseif ($driver->verification_level->value >= 1)
                <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-gold-400 flex items-center justify-center ring-2 ring-white"
                      aria-label="Workplace verified">
                    <x-icon name="badge-check" class="h-3.5 w-3.5 text-ink-900" />
                </span>
            @endif
        </div>

        <div class="flex-1 min-w-0">
            <p class="font-semibold text-ink-900 truncate">{{ $driver->name }}</p>
            <p class="text-sm text-ink-500 flex items-center gap-1">
                @if ($vehicle)
                    {{ $vehicle->make }} {{ $vehicle->model }} · {{ $vehicle->plate_number }}
                @else
                    Vehicle details pending
                @endif
            </p>
            @if ($driver->rating_avg)
                <div class="flex items-center gap-1 mt-1">
                    <x-icon name="star" class="h-3.5 w-3.5 text-gold-400 fill-current" />
                    <span class="text-sm font-mono text-ink-700">{{ number_format($driver->rating_avg, 1) }}</span>
                    <span class="text-[11px] text-ink-400">({{ $driver->rating_count }} rides)</span>
                </div>
            @endif
        </div>

        {{-- IRI road condition indicator --}}
        <div class="flex flex-col items-end gap-1">
            <span class="w-3 h-3 rounded-full {{ $iriColor }} flex-shrink-0" aria-hidden="true" title="Road: {{ ucfirst($iriCondition) }}"></span>
            <span class="text-[10px] text-ink-400 uppercase tracking-wide">{{ ucfirst($iriCondition) }}</span>
        </div>
    </div>

    {{-- Route + departure --}}
    <div class="p-4 border-b border-ink-100">
        <h3 class="font-heading text-base font-semibold text-ink-900 truncate mb-1">{{ $routeTitle }}</h3>
        <div class="flex items-center gap-3 text-sm text-ink-500">
            <span class="flex items-center gap-1">
                <x-icon name="clock" class="h-4 w-4" />
                <span>{{ $departureTime }}</span>
            </span>
            @if ($isLive)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-forest-50 text-forest-700 text-xs font-semibold wr-pulse"
                      aria-live="polite" aria-atomic="true">
                    <span class="w-1.5 h-1.5 rounded-full bg-forest-500" aria-hidden="true"></span>
                    Live now
                </span>
            @elseif ($isLeavingSoon)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gold-50 text-gold-700 text-xs font-semibold"
                      aria-label="Leaving in less than 15 minutes">
                    <x-icon name="clock" class="h-3 w-3" />
                    Leaving soon
                </span>
            @endif
        </div>
    </div>

    {{-- Seats + fare + score reasons --}}
    <div class="p-4 space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1 text-sm text-ink-600">
                    <x-icon name="users" class="h-4 w-4" />
                    <span class="font-mono text-lg font-semibold text-ink-900" data-seats="{{ $seatsLeft }}">{{ $seatsLeft }}</span>
                    <span class="text-ink-400">/ {{ $totalSeats }} seats</span>
                </span>
                @if ($matchScore !== null)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-ink-50 text-ink-700 text-xs font-mono"
                          aria-label="Match score {{ $matchScore }}%">
                        {{ $matchScore }}% match
                    </span>
                @endif
            </div>
            <span class="font-heading text-lg font-bold text-forest-600 tabular-nums"
                  aria-label="Fare {{ $fare }}">
                {{ $fare }}
            </span>
        </div>

        {{-- Score reasons chips --}}
        @if (!empty($scoreReasons))
            <div class="flex flex-wrap gap-1.5" aria-label="Match reasons">
                @foreach ($scoreReasons as $reason)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-ink-50 text-ink-600 text-xs">
                        {{ $reason }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Primary actions --}}
        <div class="flex gap-2 pt-2">
            @if ($showSoftHold && config('workride.soft_hold.enabled'))
                <button type="button"
                        wire:click="{{ $onSoftHold }}"
                        onclick="{{ $onSoftHold }}"
                        class="flex-1 btn-secondary wr-transition-fast"
                        aria-label="Hold seat for 3 minutes">
                    Hold Seat (3 min)
                </button>
                <button type="button"
                        wire:click="{{ $onBook }}"
                        onclick="{{ $onBook }}"
                        class="flex-1 btn-primary wr-transition-fast"
                        aria-label="Join this ride">
                    Join Ride
                </button>
            @else
                <button type="button"
                        wire:click="{{ $onBook }}"
                        onclick="{{ $onBook }}"
                        class="w-full btn-primary wr-transition-fast py-3 text-base font-semibold"
                        aria-label="{{ $isFree ? 'Join free ride' : 'Join ride for ' . $fare }}">{{ $isFree ? 'Join Free Ride' : 'Join Ride' }}</button>
            @endif
        </div>
    </div>
</div>