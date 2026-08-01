@extends('layouts.public')

@section('title', 'Receipt verification')

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-ink-200 bg-white p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-forest-600 text-2xl font-bold text-white">W</div>
        <h1 class="mt-4 font-heading text-2xl font-bold text-ink-900">{{ $title }}</h1>

        @if ($verified)
            <div class="mt-3 inline-block rounded-full bg-forest-50 px-4 py-1.5 text-sm font-semibold text-forest-700">
                ✓ Verified — authentic WorkRide record
            </div>
        @else
            <div class="mt-3 inline-block rounded-full bg-ink-100 px-4 py-1.5 text-sm font-semibold text-ink-500">
                Record found — no payment yet for this reference
            </div>
        @endif

        <div class="mt-6 text-left rounded-xl border border-ink-100 bg-paper p-5">
            <div class="flex justify-between py-1">
                <span class="text-sm text-ink-500">Holder</span>
                <span class="text-sm font-semibold text-ink-900">{{ $holder }}</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-sm text-ink-500">Reference</span>
                <span class="font-mono text-sm text-ink-900">{{ $reference }}</span>
            </div>
            @foreach ($rows as $label => $value)
                <div class="flex justify-between py-1">
                    <span class="text-sm text-ink-500">{{ $label }}</span>
                    <span class="text-sm font-semibold text-ink-900">{{ $value }}</span>
                </div>
            @endforeach
        </div>

        <p class="mt-6 text-xs text-ink-400">
            This page is public — scan the QR on the receipt to verify it.
            Built by amateurs, for the working class. From Abuja to the world.
        </p>
    </div>
@endsection
