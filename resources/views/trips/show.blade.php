@extends('layouts.app')

@section('title', $trip->route_name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('trips.index') }}" class="text-sm font-semibold text-forest-600 hover:underline">← Trip Board</a>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-2xl border border-ink-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">{{ $trip->corridor->short() }}</span>
                            <x-badge :status="$trip->status->value" />
                            @if ($trip->is_free_volunteer)
                                <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">FREE volunteer</span>
                            @endif
                            @if ($trip->women_only)
                                <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Women-only</span>
                            @endif
                        </div>
                        <h1 class="mt-3 font-heading text-2xl font-bold text-ink-900">{{ $trip->route_name }}</h1>
                        <p class="mt-1 text-sm text-ink-500">
                            {{ $trip->origin_text }} → {{ $trip->destination_text }}
                        </p>
                    </div>
                    <p class="font-mono text-2xl font-semibold text-ink-900">
                        ₦{{ number_format((float) $trip->fare_per_seat, 0) }}
                    </p>
                </div>

                <div class="mt-6 grid gap-4 border-t border-ink-100 pt-5 text-sm text-ink-600 sm:grid-cols-3">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-ink-400">Departure</p>
                        <p class="mt-1 font-medium text-ink-900">{{ $trip->departure_time->format('D, M j · g:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-ink-400">Seats</p>
                        <p class="mt-1 font-medium text-ink-900" x-data="tripLive({ tripId: {{ $trip->id }}, initial: {{ $trip->available_seats }} })">
                            <span x-text="seats"></span>/{{ $trip->total_seats }} left
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-ink-400">Status</p>
                        <p class="mt-1 font-medium text-ink-900">{{ $trip->status->label() }}</p>
                    </div>
                </div>

                @php
                    $minutesToDeparture = (int) ($timing['minutes_to_departure'] ?? 0);
                    $etaNext = (int) ($timing['eta_to_next_waypoint_minutes'] ?? 0);
                    $delay = (int) ($timing['delay_minutes'] ?? 0);
                @endphp
                <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-5 text-sm" aria-live="polite">
                    @if ($trip->status->value === 'scheduled' && $minutesToDeparture >= 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-forest-50 px-3 py-1.5 text-xs font-semibold text-forest-700">
                            <x-icon name="clock" class="h-3.5 w-3.5" />
                            Leaves in {{ $minutesToDeparture }} min
                        </span>
                    @endif
                    @if ($trip->status->value === 'active')
                        @if ($etaNext > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gold-800">
                                <x-icon name="map-pin" class="h-3.5 w-3.5" />
                                Next: {{ $timing['next_waypoint_label'] ?? 'next stop' }} in {{ $etaNext }} min
                            </span>
                        @endif
                        @if ($timing['eta_to_destination_minutes'] !== null)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-forest-100 px-3 py-1.5 text-xs font-semibold text-forest-800">
                                <x-icon name="target" class="h-3.5 w-3.5" />
                                ETA {{ $trip->destination_text }} ~{{ (int) $timing['eta_to_destination_minutes'] }} min
                            </span>
                        @endif
                        @if ($delay > 5)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700">
                                <x-icon name="alert" class="h-3.5 w-3.5" />
                                Delayed {{ $delay }} min
                            </span>
                        @endif
                    @endif
                </div>
            </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <div class="flex items-center gap-2 rounded-xl border border-ink-200 px-3 py-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-forest-100 font-heading text-sm font-bold text-forest-700">
                            {{ strtoupper(substr($trip->driver?->name ?? '?', 0, 1)) }}
                        </span>
                        <span>
                            <span class="block text-sm font-medium text-ink-900">{{ $trip->driver?->name }}</span>
                            <span class="block text-xs text-ink-500">
                                Verified L{{ $trip->driver?->verification_level?->value }}
                                @if ($trip->driver_rating_count)
                                    · <span class="text-gold-600">★ {{ number_format((float) $trip->driver_rating_avg, 1) }}</span>
                                    <span class="text-ink-400">({{ $trip->driver_rating_count }})</span>
                                @endif
                                @if ($driverScore)
                                    · <span class="font-semibold text-forest-700">{{ $driverScore->score }} {{ $driverScore->level->label() }} driver</span>
                                @endif
                            </span>
                        </span>
                    </div>
                    @if ($trip->vehicle)
                        <div class="flex items-center gap-2 rounded-xl border border-ink-200 px-3 py-2">
                            <span class="block text-sm font-medium text-ink-900">
                                {{ $trip->vehicle->make }} {{ $trip->vehicle->model }}
                            </span>
                            <span class="font-mono text-xs text-ink-500">{{ $trip->vehicle->plate_number }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-5">
                    <div x-data="{ copied: false }">
                        <button type="button" @click="navigator.clipboard.writeText('{{ route('trips.share', $trip) }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                class="flex items-center gap-2 rounded-xl border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:bg-ink-100">
                            <x-icon name="arrow-right" class="h-4 w-4" />
                            <span x-text="copied ? 'Link copied!' : 'Share this ride'"></span>
                        </button>
                    </div>
                    @if ($isParticipant && in_array($trip->status->value, ['scheduled', 'active'], true))
                        <form method="POST" action="{{ route('trips.sos', $trip) }}">
                            @csrf
                            <button class="flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                <x-icon name="alert" class="h-4 w-4" />
                                SOS
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @php $waypoints = $trip->waypoints()->get(); @endphp
            @if ($waypoints->isNotEmpty())
                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-heading font-semibold text-ink-900">Ride progress</h2>
                        @if ($trip->status->value === 'active')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-forest-50 px-3 py-1 text-xs font-semibold text-forest-700">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-forest-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-forest-500"></span>
                                </span>
                                Live
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-ink-500">
                        {{ $trip->origin_text }} → {{ $trip->destination_text }}
                    </p>

                    <div class="mt-5">
                        <x-trip.progress-tracker
                            :progress="$progress"
                            :live="$trip->status->value === 'active' ? ['available_seats' => $trip->available_seats] : false"
                            :id="$trip->id" />
                    </div>
                </div>
            @endif

            @if ($trip->driver_id === $user->id)
                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <h2 class="font-heading font-semibold text-ink-900">Passengers</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($trip->bookings as $booking)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-ink-100 bg-paper px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-ink-800">{{ $booking->passenger?->name }}</p>
                                    <p class="text-xs text-ink-500">
                                        @if ($booking->status->value === 'requested')
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon name="ticket" class="h-3.5 w-3.5" />
                                                Requested to join
                                                @if ($booking->share_code)
                                                    · ride code {{ $booking->share_code }}
                                                @endif
                                            </span>
                                        @else
                                            {{ $booking->payment_method->label() }} · ₦{{ number_format((float) $booking->fare_paid, 2) }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-badge :status="$booking->status->value" />
                                    @if ($booking->status->value === 'requested')
                                        <form method="POST" action="{{ route('bookings.approve', $booking) }}">
                                            @csrf
                                            <button class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('bookings.decline', $booking) }}">
                                            @csrf
                                            <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-50">Decline</button>
                                        </form>
                                    @endif
                                    @if ($booking->status->value === 'confirmed')
                                        <form method="POST" action="{{ route('bookings.board', $booking) }}">
                                            @csrf
                                            <button class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">Board</button>
                                        </form>
                                        <form method="POST" action="{{ route('bookings.no-show', $booking) }}">
                                            @csrf
                                            <button class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-700 transition hover:bg-ink-100">No-show</button>
                                        </form>
                                    @endif
                                    @if ((float) $booking->fare_paid > 0 && in_array($booking->status->value, ['boarded', 'completed'], true))
                                        <a href="{{ route('receipts.earnings', $booking) }}" class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:bg-white">
                                            Earnings receipt
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">No passengers yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($canStart || $canComplete || $canCancelTrip)
                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <h2 class="font-heading font-semibold text-ink-900">Driver actions</h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        @if ($canStart)
                            <form method="POST" action="{{ route('trips.start', $trip) }}">
                                @csrf
                                <button class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">▶ Start trip</button>
                            </form>
                        @endif
                        @if ($canComplete)
                            <form method="POST" action="{{ route('trips.complete', $trip) }}">
                                @csrf
                                <button class="rounded-xl bg-forest-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-800">■ Complete trip</button>
                            </form>
                        @endif
                        @if ($canCancelTrip)
                            <form method="POST" action="{{ route('trips.cancel', $trip) }}">
                                @csrf
                                <button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">Cancel trip</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            @if ($trip->status->value === 'active' && $trip->driver_id === $user->id)
                <div class="rounded-2xl border border-ink-200 bg-white p-6"
                     x-data="roadSensor({ threshold: {{ config('workride.road_sensor.pothole_z_threshold') }}, endpoint: '/api/v1/road-events' })">
                    <h2 class="font-heading font-semibold text-ink-900">Road sensor</h2>
                    <p class="mt-1 text-sm text-ink-500">
                        Your phone detects potholes (accelerometer Z &gt; {{ config('workride.road_sensor.pothole_z_threshold') }})
                        while you drive — anonymised into the Road Intelligence map.
                    </p>
                    <div class="mt-4 flex items-center gap-3 rounded-xl bg-paper px-4 py-3">
                        <span class="relative flex h-3 w-3">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-forest-400 opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-forest-500"></span>
                        </span>
                        <span class="text-sm font-medium text-ink-700" x-text="status === 'listening' ? 'Listening for potholes…' : status === 'unsupported' ? 'Device motion not supported' : status === 'denied' ? 'Sensor permission denied' : 'Sensor idle'"></span>
                        <span class="ml-auto font-mono text-sm text-ink-500" x-show="hits > 0"><span x-text="hits"></span> hit(s)</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            @if ($isParticipant && in_array($trip->status->value, ['scheduled', 'active'], true))
                <x-navigation-anim
                    origin="{{ $trip->origin_text }}"
                    destination="{{ $trip->destination_text }}"
                    label="Your car is on the way — live route to {{ $trip->destination_text }}." />
            @endif

            @if ($womenOnlyBlocked && ! $myBooking && in_array($trip->status->value, ['scheduled', 'active'], true) && $trip->available_seats > 0)
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-6">
                    <h2 class="font-heading font-semibold text-rose-900">Women-only ride</h2>
                    <p class="mt-1 text-sm text-rose-700">
                        This trip is reserved for women riders. Update your gender preference in
                        <a href="{{ route('profile.edit') }}" class="font-semibold underline">Profile & safety</a>
                        to see and book it.
                    </p>
                </div>
            @elseif (! $myBooking && ! $canStart && $trip->driver_id !== $user->id && in_array($trip->status->value, ['scheduled', 'active']) && $trip->available_seats > 0 && $canBookForm)
                <div class="rounded-2xl border border-forest-200 bg-white p-6">
                    <h2 class="font-heading font-semibold text-ink-900">
                        @if ($trip->is_free_volunteer)
                            Ride free
                        @else
                            Book a seat
                        @endif
                    </h2>
                    <div class="mt-4">
                        <x-ui.progress-wizard :steps="[
                            ['label' => 'Destination', 'eta' => $trip->origin_text],
                            ['label' => 'Options'],
                            ['label' => 'Payment', 'eta' => 'seat held on confirm'],
                            ['label' => 'Confirmed'],
                        ]" :current="2" :show-time="true" />
                    </div>
                    @if (! $trip->is_free_volunteer)
                        <p class="mt-1 text-sm text-ink-500">
                            Pay wallet, subsidy, cash or ride-credit. Fixed price {{ $trip->corridor->short() }} — no surge.
                        </p>
                    @endif
                    <x-payment-picker
                        :action="route('bookings.store', $trip)"
                        :fare="$trip->fare_per_seat"
                        :is-free="$trip->is_free_volunteer"
                        :wallet-balance="$user->wallet ? (float) $user->wallet->cash_balance + (float) $user->wallet->earned_balance : 0"
                        :subsidy-balance="$user->wallet?->subsidy_credits ?? 0"
                        :can-subsidy="$user->canBookBenefits()"
                        :can-ride-credit="config('workride.time_bank.enabled') && $user->verification_level->value >= \App\Enums\VerificationLevel::NinVerified->value" />
                    @if (config('workride.soft_hold.enabled'))
                        <form method="POST" action="{{ route('bookings.soft-hold', $trip) }}" class="mt-4 border-t border-ink-100 pt-4">
                            @csrf
                            <p class="text-xs text-ink-500">
                                Not sure yet? Reserve this seat for {{ config('workride.soft_hold.ttl_minutes', 3) }} minutes —
                                nothing is charged until you confirm from My Rides.
                            </p>
                            <button type="submit"
                                    class="mt-2 w-full rounded-xl border border-gold-400 px-4 py-2.5 text-sm font-semibold text-gold-800 transition hover:bg-gold-50">
                                Hold my seat · {{ config('workride.soft_hold.ttl_minutes', 3) }} min
                            </button>
                        </form>
                    @endif
                </div>
            @elseif (! $myBooking && $user->canBook() && $trip->is_free_volunteer && ! $user->canBookBenefits() && in_array($trip->status->value, ['scheduled', 'active']) && $trip->available_seats > 0)
                <div class="rounded-2xl border border-gold-200 bg-gold-50/60 p-6">
                    <h2 class="font-heading font-semibold text-ink-900">Volunteer rides are for verified workers</h2>
                    <p class="mt-1 text-sm text-ink-700">
                        Free rides are the supply-bootstrap benefit — complete your
                        <a href="{{ route('verification.index') }}" class="font-semibold text-forest-700 underline hover:text-forest-900">workplace verification</a>
                        to ride for free and keep volunteer drivers rewarded.
                    </p>
                </div>
            @elseif (! $myBooking && $trip->driver_id !== $user->id && in_array($trip->status->value, ['scheduled', 'active']))
                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <h2 class="font-heading font-semibold text-ink-900">
                        @if ($trip->available_seats < 1)
                            Trip is full
                        @else
                            I want this journey
                        @endif
                    </h2>
                    <p class="mt-1 text-sm text-ink-500">
                        @if ($trip->available_seats < 1)
                            Every seat is taken, but demand talks — join the list so the driver (and Ops) knows this ride is wanted.
                        @else
                            @if ($interestCount > 0)
                                <strong>{{ $interestCount }}</strong> {{ $interestCount === 1 ? 'person wants' : 'people want' }} this trip. Register interest and when a seat frees up, book instantly.
                            @else
                                Be the first to register interest — the driver (and Ops) sees this ride is wanted.
                            @endif
                        @endif
                    </p>
                    @if ($myInterest)
                        <p class="mt-3 inline-flex items-center gap-2 rounded-xl bg-forest-50 px-4 py-2 text-sm font-semibold text-forest-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-forest-500"></span> You're on the list
                        </p>
                    @else
                        <form method="POST" action="{{ route('trips.interest', $trip) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="w-full rounded-xl border border-forest-600 px-4 py-3 text-sm font-semibold text-forest-700 transition hover:bg-forest-50">
                                @if ($trip->available_seats < 1)
                                    Notify me when a seat frees up
                                @else
                                    I want this journey
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            @if ($myBooking)
                <div class="rounded-2xl border border-forest-200 bg-white p-6">
                    <h2 class="font-heading font-semibold text-ink-900">Your booking</h2>
                    <div class="mt-3 space-y-2 text-sm text-ink-700">
                        <p>Status: <x-badge :status="$myBooking->status->value" /></p>
                        @if ($myBooking->status->value === 'requested')
                            <p class="inline-flex items-center gap-1.5 rounded-xl bg-gold-50 px-3 py-2 text-gold-800">
                                <x-icon name="ticket" class="h-4 w-4" />
                                Request sent — the driver will approve or decline it. Your seat is held the moment they approve.
                            </p>
                        @else
                            <p>Paid: ₦{{ number_format((float) $myBooking->fare_paid, 2) }} · {{ $myBooking->payment_method->label() }}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('bookings.cancel', $myBooking) }}" class="mt-4">
                        @csrf
                        <button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                            {{ $myBooking->status->value === 'requested' ? 'Withdraw request' : 'Cancel booking' }}
                        </button>
                    </form>
                </div>
            @endif

            @if ($isParticipant && in_array($trip->status->value, ['scheduled', 'active'], true))
                <div class="rounded-2xl border border-forest-200 bg-forest-50/60 p-6">
                    <h2 class="font-heading font-semibold text-ink-900">Connect guide</h2>
                    <p class="mt-2 text-sm text-ink-700">
                        Find where to meet the vehicle — live position or next boarding point, with walking distance and ETA.
                    </p>
                    <a href="{{ route('trips.guide.show', $trip) }}"
                       class="mt-4 inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Open connect guide
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            @endif

            @if ($isParticipant)
                <div class="rounded-2xl border border-ink-200 bg-white p-6"
                     x-data="tripChat({
                         tripId: {{ $trip->id }},
                         canChat: {{ $isParticipant ? 'true' : 'false' }},
                         messages: {{ $trip->chatMessages->map(fn ($m) => [
                             'id' => $m->id,
                             'sender_id' => $m->sender_id,
                             'sender_name' => $m->sender?->name,
                             'message' => $m->message,
                             'created_at' => $m->created_at?->toIso8601String(),
                         ])->toJson() }}
                     })">
                    <h2 class="font-heading font-semibold text-ink-900">Trip chat</h2>
                    <div x-ref="messages" class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                        <template x-for="message in messages" :key="message.id">
                            <div class="flex" :class="message.sender_id == window.App.userId ? 'justify-end' : 'justify-start'">
                                <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm"
                                     :class="message.sender_id == window.App.userId ? 'bg-forest-600 text-white' : 'bg-paper text-ink-800 border border-ink-100'">
                                    <p class="mb-0.5 text-xs font-semibold"
                                       :class="message.sender_id == window.App.userId ? 'text-forest-100' : 'text-ink-500'"
                                       x-text="message.sender_name"></p>
                                    <p x-text="message.message"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                    <form @submit.prevent="send" class="mt-4 flex gap-2">
                        <input type="text" x-model="message" maxlength="2000" placeholder="Message the driver…"
                               class="flex-1 rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                        <button type="submit" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700"
                                :disabled="sending || ! message.trim()">
                            Send
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
