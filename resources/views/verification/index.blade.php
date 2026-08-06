@extends('layouts.app')

@section('title', 'Verification')

@section('content')
    <div class="mb-8">
        <h1 class="font-heading text-2xl font-bold text-ink-900">Verification</h1>
        <p class="mt-1 text-sm text-ink-500">
            Your NIN is hashed with SHA-256 — the raw number is <strong>never stored</strong> (NDPR/NDPA compliant).
        </p>
    </div>

    @php
        $verificationStep = $user->hasVerifiedPhone() ? 1 : 0;
        if ((int) ($user->verification_level?->value ?? 0) >= 1) {
            $verificationStep = 2;
        }
        if ((int) ($user->verification_level?->value ?? 0) >= 2) {
            $verificationStep = 3;
        }
    @endphp
    <div class="mb-6 rounded-2xl border border-ink-200 bg-white p-6">
        <x-ui.progress-wizard :steps="[
            ['label' => 'Phone', 'eta' => $user->hasVerifiedPhone() ? 'done' : '60 seconds'],
            ['label' => 'Workplace', 'eta' => 'Level 1'],
            ['label' => 'NIN', 'eta' => 'Level 2'],
            ['label' => 'Driver docs', 'eta' => 'Level 3'],
        ]" :current="$verificationStep" :show-time="true" />
        <p class="mt-4 text-sm text-ink-500">
            Each level unlocks more: phone books instantly · Level 1 unlocks subsidies, volunteer rides &amp; employer coverage ·
            Level 2 unlocks ride-credit &amp; transfers · Level 3 lets you publish paid rides.
        </p>
    </div>

    @if (! $user->hasVerifiedPhone())
        <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-forest-200 bg-forest-50 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-forest-600 px-3 py-1 text-xs font-semibold text-white">Tier 0 · Instant booking</span>
                <h2 class="mt-2 font-heading font-semibold text-ink-900">Verify your phone, book a ride in 60 seconds</h2>
                <p class="mt-1 text-sm text-ink-600">
                    An SMS code proves your number is live — no ID needed yet. You pay the normal fixed fare;
                    subsidies, volunteer rides and employer coverage unlock at Level 1+.
                </p>
            </div>
            <a href="{{ route('verification.phone') }}" class="shrink-0 rounded-xl bg-forest-600 px-5 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-forest-700">
                Verify my phone
            </a>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-forest-100 text-sm font-bold text-forest-700">1</span>
            <h2 class="mt-3 font-heading font-semibold text-ink-900">Workplace ID</h2>
            <p class="mt-1 text-sm text-ink-500">Level 1. Confirms you work at an MDA. Unlocks subsidies, volunteer rides and employer coverage.</p>

            <form method="POST" action="{{ route('verification.workplace') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <select name="workplace_id" required class="w-full rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <option value="">Select your workplace</option>
                    @foreach (\App\Models\Workplace::orderBy('name')->get() as $workplace)
                        <option value="{{ $workplace->id }}" {{ $user->workplace_id === $workplace->id ? 'selected' : '' }}>
                            {{ $workplace->name }} @if ($workplace->acronym)({{ $workplace->acronym }})@endif
                        </option>
                    @endforeach
                </select>
                <input type="file" name="document" accept="image/*" class="block w-full text-sm text-ink-500 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-ink-700">
                <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Submit workplace
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-forest-100 text-sm font-bold text-forest-700">2</span>
            <h2 class="mt-3 font-heading font-semibold text-ink-900">NIN</h2>
            <p class="mt-1 text-sm text-ink-500">Level 2. 11 digits. Stored as hash + last 4 only.</p>

            <form method="POST" action="{{ route('verification.nin') }}" class="mt-4 space-y-3">
                @csrf
                <input type="tel" name="nin" pattern="[0-9]{11}" maxlength="11" inputmode="numeric"
                    placeholder="12345678901"
                    class="w-full rounded-xl border border-ink-300 px-4 py-2.5 font-mono text-sm tracking-widest focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Submit NIN
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gold-100 text-sm font-bold text-gold-700">3</span>
            <h2 class="mt-3 font-heading font-semibold text-ink-900">Driver docs</h2>
            <p class="mt-1 text-sm text-ink-500">Level 3. Coming in a later sprint. Required to publish paid rides.</p>
            <div class="mt-4 rounded-xl bg-paper px-4 py-3 text-sm text-ink-500">
                Volunteer free rides need only Level 1.
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-6">
        <h2 class="font-heading font-semibold text-ink-900">Your submissions</h2>
        <div class="mt-4 space-y-3">
            @forelse ($user->verifications->sortByDesc('updated_at') as $verification)
                <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-ink-800">{{ \Str::title(str_replace('_', ' ', $verification->type)) }}</p>
                        <p class="text-xs text-ink-500">
                            @if ($verification->type === 'nin')
                                NIN •••• {{ $verification->nin_last4 }}
                            @elseif ($verification->workplace)
                                {{ $verification->workplace->name }}
                            @endif
                            · submitted {{ $verification->updated_at->diffForHumans() }}
                        </p>
                        @if ($verification->admin_note)
                            <p class="mt-1 text-xs text-ink-500">Admin note: {{ $verification->admin_note }}</p>
                        @endif
                    </div>
                    <x-badge :status="$verification->status" />
                </div>
            @empty
                <p class="text-sm text-ink-500">No submissions yet.</p>
            @endforelse
        </div>
    </div>
@endsection
