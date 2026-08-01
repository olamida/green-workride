@extends('layouts.app')

@section('title', 'My Rides')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-ink-900">My Rides</h1>
            <p class="mt-1 text-sm text-ink-500">
                Every trip you've booked, and trips you're driving.
            </p>
        </div>
        <a href="{{ route('trips.index') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
            Find a ride
        </a>
    </div>

    <div class="space-y-8">
        <section>
            <h2 class="mb-3 font-heading font-semibold text-ink-900">As passenger</h2>
            <div class="space-y-4">
                @forelse ($user->bookings as $booking)
                    <div class="rounded-2xl border border-ink-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <a href="{{ route('trips.show', $booking->trip) }}" class="font-heading font-semibold text-ink-900 hover:text-forest-700">
                                    {{ $booking->trip?->route_name }}
                                </a>
                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $booking->trip?->origin_text }} → {{ $booking->trip?->destination_text }}
                                </p>
                                <p class="mt-2 text-xs text-ink-500">
                                    {{ $booking->trip?->departure_time?->format('D, M j · g:i A') }}
                                    · {{ $booking->payment_method->label() }}
                                    · ₦{{ number_format((float) $booking->fare_paid, 2) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-badge :status="$booking->status->value" />
                                @if (in_array($booking->status->value, ['confirmed', 'requested'], true))
                                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                                        @csrf
                                        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-ink-300 bg-white p-8 text-center">
                        <p class="text-sm text-ink-500">No bookings yet. Find a corridor trip on the board.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="mb-3 font-heading font-semibold text-ink-900">As driver</h2>
            <div class="space-y-4">
                @forelse ($user->trips as $trip)
                    <div class="rounded-2xl border border-ink-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <a href="{{ route('trips.show', $trip) }}" class="font-heading font-semibold text-ink-900 hover:text-forest-700">
                                    {{ $trip->route_name }}
                                </a>
                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $trip->departure_time->format('D, M j · g:i A') }}
                                    · {{ $trip->available_seats }}/{{ $trip->total_seats }} seats
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-badge :status="$trip->status->value" />
                                <a href="{{ route('trips.show', $trip) }}" class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">
                                    Manage
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-ink-300 bg-white p-8 text-center">
                        <p class="text-sm text-ink-500">You haven't published any trips yet.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
