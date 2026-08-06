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

    @php
        $defaultTab = $activeBookings->isNotEmpty() ? 'active' : ($upcomingBookings->isNotEmpty() ? 'upcoming' : 'past');
    @endphp

    <div x-data="{ tab: '{{ $defaultTab }}' }" class="space-y-6">
        {{-- Segmented control — land on the rides that need attention NOW. --}}
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter rides">
            <button type="button" role="tab" :aria-selected="tab === 'active'" @click="tab = 'active'"
                    :class="tab === 'active' ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition">
                Active
                @if ($activeBookings->isNotEmpty())
                    <span :class="tab === 'active' ? 'text-gold-300' : 'text-ink-400'" class="font-mono text-xs">{{ $activeBookings->count() }}</span>
                @endif
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'upcoming'" @click="tab = 'upcoming'"
                    :class="tab === 'upcoming' ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition">
                Upcoming
                @if ($upcomingBookings->isNotEmpty())
                    <span :class="tab === 'upcoming' ? 'text-gold-300' : 'text-ink-400'" class="font-mono text-xs">{{ $upcomingBookings->count() }}</span>
                @endif
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'past'" @click="tab = 'past'"
                    :class="tab === 'past' ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition">
                Past
                @if ($pastBookings->isNotEmpty())
                    <span :class="tab === 'past' ? 'text-gold-300' : 'text-ink-400'" class="font-mono text-xs">{{ $pastBookings->count() }}</span>
                @endif
            </button>
        </div>

        {{-- Active — on the road now or leaving within the next 30 minutes. --}}
        <section x-show="tab === 'active'" x-cloak role="tabpanel">
            <div class="space-y-4">
                @forelse ($activeBookings as $booking)
                    @include('bookings._booking-card', ['booking' => $booking, 'user' => $user])
                @empty
                    <div class="rounded-2xl border border-dashed border-ink-300 bg-white p-8 text-center">
                        <p class="font-heading font-semibold text-ink-900">No rides leaving soon</p>
                        <p class="mt-1 text-sm text-ink-500">Nothing is on the road right now. Head to the board and grab a seat.</p>
                        <a href="{{ route('trips.index') }}" class="mt-4 inline-flex rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                            Find a ride
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Upcoming — confirmed seats on future scheduled trips. --}}
        <section x-show="tab === 'upcoming'" x-cloak role="tabpanel">
            <div class="space-y-4">
                @forelse ($upcomingBookings as $booking)
                    @include('bookings._booking-card', ['booking' => $booking, 'user' => $user])
                @empty
                    <div class="rounded-2xl border border-dashed border-ink-300 bg-white p-8 text-center">
                        <p class="font-heading font-semibold text-ink-900">Nothing planned ahead yet</p>
                        <p class="mt-1 text-sm text-ink-500">Book a day-ahead seat so you don't scramble at 6:45am.</p>
                        <a href="{{ route('trips.index') }}" class="mt-4 inline-flex rounded-xl border border-forest-600 px-4 py-2 text-sm font-semibold text-forest-700 transition hover:bg-forest-50">
                            Plan ahead
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Past — completed, cancelled or no-show rides. --}}
        <section x-show="tab === 'past'" x-cloak role="tabpanel">
            <div class="space-y-4">
                @forelse ($pastBookings as $booking)
                    @include('bookings._booking-card', ['booking' => $booking, 'user' => $user])
                @empty
                    <div class="rounded-2xl border border-dashed border-ink-300 bg-white p-8 text-center">
                        <p class="font-heading font-semibold text-ink-900">No past rides yet</p>
                        <p class="mt-1 text-sm text-ink-500">Completed rides land here with receipts and ratings.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Driver trips — kept below the passenger timeline. --}}
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
                        @foreach ($trip->bookings as $booking)
                            @if ($booking->status->value === 'completed' && ! $booking->ratingBy($user->id))
                                <div class="mt-4 border-t border-ink-100 pt-4">
                                    <x-rating-form
                                        :action="route('ratings.store', $booking)"
                                        title="Rate passenger — {{ $booking->passenger?->name }}"
                                        cta="Submit rating" />
                                </div>
                            @endif
                        @endforeach
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
