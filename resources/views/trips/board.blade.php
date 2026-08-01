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

    <div class="mb-8 flex flex-wrap gap-3">
        <a href="{{ route('trips.index') }}" @class([
            'rounded-full px-4 py-2 text-sm font-semibold transition',
            'bg-ink-900 text-white' => ! $corridor,
            'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => $corridor,
        ])>All corridors</a>
        @foreach (\App\Enums\Corridor::cases() as $option)
            <a href="{{ route('trips.index', ['corridor' => $option->value]) }}" @class([
                'rounded-full px-4 py-2 text-sm font-semibold transition',
                'bg-ink-900 text-white' => $corridor?->value === $option->value,
                'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' => $corridor?->value !== $option->value,
            ])>
                <span class="mr-1 inline-block h-2 w-2 animate-pulse rounded-full bg-forest-500"></span>
                {{ $option->label() }}
            </a>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($trips as $trip)
            <a href="{{ route('trips.show', $trip) }}" class="block rounded-2xl border border-ink-200 bg-white p-5 transition hover:border-forest-300 hover:shadow-md">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">{{ $trip->corridor->short() }}</span>
                            @if ($trip->is_free_volunteer)
                                <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">FREE volunteer</span>
                            @endif
                        </div>
                        <p class="mt-3 font-heading text-lg font-semibold text-ink-900">{{ $trip->route_name }}</p>
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
                    @if ($trip->current_lat)
                        <span class="inline-flex items-center gap-1 text-forest-700">
                            <span class="h-2 w-2 rounded-full bg-forest-500"></span>
                            Live
                        </span>
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-ink-300 bg-white p-10 text-center">
                <p class="font-heading text-lg font-semibold text-ink-900">No trips departing soon</p>
                <p class="mt-1 text-sm text-ink-500">
                    Check another corridor, or be the first to publish on this route.
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
