@php
    $trip = $booking->trip;
    $canOpenGuide = $trip
        && in_array($trip->status->value, ['scheduled', 'active'], true)
        && in_array($booking->status->value, ['confirmed', 'boarded'], true);
    $canCancel = in_array($booking->status->value, ['confirmed', 'requested'], true);
@endphp
<div class="rounded-2xl border border-ink-200 bg-white p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('trips.show', $trip) }}" class="font-heading font-semibold text-ink-900 hover:text-forest-700">
                {{ $trip?->route_name }}
            </a>
            <p class="mt-1 text-sm text-ink-500">
                {{ $trip?->origin_text }} → {{ $trip?->destination_text }}
            </p>
            <p class="mt-2 text-xs text-ink-500">
                @if ($trip?->corridor)
                    <span class="mr-1.5 inline-block rounded-full bg-forest-50 px-2 py-0.5 text-xs font-semibold text-forest-700">
                        {{ $trip->corridor->short() }}
                    </span>
                @endif
                {{ $trip?->departure_time?->format('D, M j · g:i A') }}
                · {{ $booking->payment_method->label() }}
                · ₦{{ number_format((float) $booking->fare_paid, 2) }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :status="$booking->status->value" />
            @if ($canOpenGuide)
                <a href="{{ route('trips.guide.show', $trip) }}"
                   class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">
                    Open guide
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            @endif
            @if ($canCancel)
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                    @csrf
                    <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">
                        Cancel
                    </button>
                </form>
            @endif
            @if ((float) $booking->fare_paid > 0)
                <a href="{{ route('receipts.booking', $booking) }}" class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:bg-paper">
                    Receipt
                </a>
            @endif
        </div>
    </div>
    @if ($booking->status->value === 'completed' && $trip?->status?->value === 'completed' && ! $booking->ratingBy($user->id))
        <div class="mt-4 border-t border-ink-100 pt-4">
            <x-rating-form
                :action="route('ratings.store', $booking)"
                title="Rate your driver — {{ $trip?->driver?->name }}"
                cta="Submit rating" />
        </div>
    @endif
</div>
