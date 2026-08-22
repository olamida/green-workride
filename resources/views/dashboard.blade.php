@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-ink-900">Good day, {{ Str::before($user->name, ' ') }} 👋</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $user->workplace?->name ?? 'No workplace attached yet' }}
                @if ($user->isAdmin())
                    <span class="ml-2 rounded-full bg-ink-900 px-2 py-0.5 text-xs font-medium text-white">Admin</span>
                @endif
            </p>
        </div>
        @if ($user->verification_level->value < 3)
            <a href="{{ route('verification.index') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                Complete verification → book instantly
            </a>
        @endif
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Your ID Don Complete?</p>
            <p class="mt-2 font-heading text-lg font-semibold text-ink-900">ID Level: {{ $user->verification_level->value }}</p>
            <p class="mt-1 text-xs text-ink-500">Phone ✓, Office ID ✓, NIN ✓ — Your ID don complete?</p>
            <div class="mt-3 flex gap-1">
                @for ($i = 0; $i < 3; $i++)
                    <span class="h-1.5 flex-1 rounded-full {{ $user->verification_level->value > $i ? 'bg-forest-500' : 'bg-ink-200' }}"></span>
                @endfor
            </div>
            @if (! $user->hasVerifiedPhone())
                <a href="{{ route('verification.phone') }}" class="mt-3 inline-block rounded-lg border border-forest-300 px-3 py-1.5 text-xs font-semibold text-forest-700 hover:bg-forest-50">
                    Verify phone → book instantly
                </a>
            @else
                <p class="mt-3 text-xs font-medium text-forest-700">✓ Phone verified</p>
            @endif
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Wallet</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">₦{{ number_format($user->wallet?->cash_balance ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Subsidy: <span class="font-mono font-medium text-forest-700">₦{{ number_format($user->wallet?->subsidy_credits ?? 0, 2) }}</span></p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">CO₂ saved</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">{{ number_format($user->impactStat?->co2_saved_kg ?? 0, 2) }} kg</p>
            <p class="mt-1 text-xs text-ink-500">≈ {{ number_format($user->impactStat?->trees_equivalent ?? 0, 1) }} trees</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Trips</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">{{ $user->impactStat?->total_trips ?? 0 }}</p>
            <p class="mt-1 text-xs text-ink-500">fuel saved: {{ number_format($user->impactStat?->fuel_saved_litres ?? 0, 1) }} L</p>
        </div>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <h2 class="font-heading font-semibold text-ink-900">Verification status</h2>
            <div class="mt-4 space-y-3">
                @forelse ($user->verifications as $verification)
                    <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink-800">{{ \Str::title(str_replace('_', ' ', $verification->type)) }}</p>
                            <p class="text-xs text-ink-500">
                                @if ($verification->type === 'nin')
                                    NIN •••• {{ $verification->nin_last4 }}
                                @elseif ($verification->workplace)
                                    {{ $verification->workplace->name }}
                                @endif
                            </p>
                        </div>
                        <x-badge :status="$verification->status" />
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No verification submitted yet.</p>
                @endforelse
            </div>
            <a href="{{ route('verification.index') }}" class="mt-4 inline-block text-sm font-semibold text-forest-600 hover:underline">
                Manage your ID →
            </a>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <h2 class="font-heading font-semibold text-ink-900">Your motor is live</h2>
            <p class="mt-2 text-sm text-ink-500">
                Verified colleagues wey dey commot for motor, fixed prices, no surge — find a ride or publish your own.
            </p>
            <div class="mt-4 flex gap-3">
                <span class="rounded-full bg-forest-50 px-3 py-1 text-xs font-medium text-forest-700">KUBWA → CBD</span>
                <span class="rounded-full bg-forest-50 px-3 py-1 text-xs font-medium text-forest-700">NYANYA → IDU</span>
                <span class="rounded-full bg-forest-50 px-3 py-1 text-xs font-medium text-forest-700">LUGBE → CBD</span>
            </div>
            <div class="mt-5">
                <x-demand-map-anim label="Live demand on your route — 12 people waiting at Berger." />
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('trips.index') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Find a ride
                </a>
                @if (auth()->user()->canDriveVolunteer())
                    <a href="{{ route('trips.create') }}" class="rounded-xl border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:bg-ink-100">
                        Publish a trip
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
