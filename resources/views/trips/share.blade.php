@extends('layouts.public')

@section('title', $trip->route_name)

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">{{ $trip->corridor->short() }}</span>
                        <x-badge :status="$trip->status->value" />
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
                    <p class="mt-1 font-medium text-ink-900">{{ $trip->available_seats }}/{{ $trip->total_seats }} left</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-ink-400">Driver</p>
                    <p class="mt-1 font-medium text-ink-900">
                        {{ $trip->driver?->name }}
                        <span class="text-xs font-normal text-ink-500">Verified L{{ $trip->driver?->verification_level?->value }}</span>
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-xl bg-paper px-4 py-3 text-sm text-ink-600">
                Fixed anti-surge fare · verified colleagues only · NIN-hashed, never stored.
            </div>

            <a href="{{ route('login') }}" class="mt-6 block w-full rounded-xl bg-forest-600 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-forest-700">
                Sign in to book this seat
            </a>
        </div>
    </div>
@endsection
