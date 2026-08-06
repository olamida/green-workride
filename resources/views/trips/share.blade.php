@extends('layouts.public')

@section('title', $trip->route_name)

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
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
                    @if ($trip->is_free_volunteer)
                        Free volunteer ride
                    @else
                        ₦{{ number_format((float) $trip->fare_per_seat, 0) }}
                    @endif
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

            @php
                $shareUrl = route('trips.share', $trip).(auth()->user() ? '?ref='.auth()->user()->id : '');
                $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode(
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(140)->generate($shareUrl)
                );
            @endphp

            <div class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto]">
                <div class="rounded-xl bg-paper px-4 py-3 text-sm text-ink-600">
                    <p class="text-xs uppercase tracking-wider text-ink-400">Ride code</p>
                    <p class="mt-1 font-mono text-lg font-semibold tracking-widest text-ink-900">{{ $trip->share_code }}</p>
                    <p class="mt-2">Fixed anti-surge fare · verified colleagues only · NIN-hashed, never stored.</p>
                </div>
                <div class="flex items-center justify-center rounded-xl border border-ink-200 p-2">
                    <img src="{{ $qrDataUri }}" alt="Scan to open this ride" class="h-24 w-24" width="96" height="96">
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <button type="button"
                    x-data="{ copied: false }"
                    @click="navigator.clipboard?.writeText('{{ $shareUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                    class="flex-1 rounded-xl border border-forest-200 bg-forest-50 px-4 py-3 text-sm font-semibold text-forest-800 transition hover:border-forest-300"
                    style="min-height:44px">
                    <span x-text="copied ? 'Link copied!' : 'Copy ride link'"></span>
                </button>
                <button type="button"
                    @click="navigator.share ? navigator.share({ title: '{{ $trip->route_name }}', text: '{{ $trip->origin_text }} → {{ $trip->destination_text }}', url: '{{ $shareUrl }}' }) : navigator.clipboard?.writeText('{{ $shareUrl }}')"
                    class="flex-1 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-700"
                    style="min-height:44px">
                    Share ride
                </button>
            </div>

            <a href="{{ route('login') }}" class="mt-4 block w-full rounded-xl bg-ink-900 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-ink-950" style="min-height:44px">
                Sign in to book this seat
            </a>

            <p class="mt-3 text-center text-xs text-ink-400">
                Scan the QR or open the link — anyone on this ride code can find it again from their trip board.
            </p>
        </div>
    </div>
@endsection
