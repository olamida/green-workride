@extends('layouts.public')

@section('title', 'Certificate verification')

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-ink-200 bg-white p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-forest-600 text-2xl font-bold text-white">W</div>
        <h1 class="mt-4 font-heading text-2xl font-bold text-ink-900">WorkRide certificate</h1>

        @if ($verified)
            <div class="mt-3 inline-block rounded-full bg-forest-50 px-4 py-1.5 text-sm font-semibold text-forest-700">
                ✓ Verified — authentic WorkRide record
            </div>
        @else
            <div class="mt-3 inline-block rounded-full bg-ink-100 px-4 py-1.5 text-sm font-semibold text-ink-500">
                Record found — no {{ $type === 'co2' ? 'CO₂ savings' : 'fuel savings' }} yet for this metric
            </div>
        @endif

        <div class="mt-6 text-left rounded-xl border border-ink-100 bg-paper p-5">
            <div class="flex justify-between py-1">
                <span class="text-sm text-ink-500">Holder</span>
                <span class="text-sm font-semibold text-ink-900">{{ $user->name }}</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-sm text-ink-500">Shared trips</span>
                <span class="text-sm font-semibold text-ink-900">{{ $stat->total_trips }}</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-sm text-ink-500">CO₂ saved</span>
                <span class="font-mono text-sm font-semibold text-forest-700">{{ number_format((float) $stat->co2_saved_kg, 1) }} kg</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-sm text-ink-500">Fuel saved</span>
                <span class="font-mono text-sm font-semibold text-ink-900">{{ number_format((float) $stat->fuel_saved_litres, 1) }} L</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-sm text-ink-500">Green level</span>
                <span class="text-sm font-semibold text-ink-900">{{ $stat->level }}/5</span>
            </div>
        </div>

        <p class="mt-6 text-xs text-ink-400">
            This page is public — scan the QR on the certificate to verify it.
            Built by amateurs, for the working class. From Abuja to the world.
        </p>
    </div>
@endsection
