@extends('layouts.app')

@section('title', 'Verify your phone')

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="mb-8">
            <span class="inline-flex items-center gap-2 rounded-full bg-forest-600 px-3 py-1 text-xs font-semibold text-white">Tier 0 · Instant booking</span>
            <h1 class="mt-3 font-heading text-2xl font-bold text-ink-900">Verify your phone</h1>
            <p class="mt-1 text-sm text-ink-500">
                Book a ride in 60 seconds — no ID needed yet. Benefits (subsidies, volunteer rides,
                employer coverage, ride credits) unlock after Level 1 workplace verification.
            </p>
        </div>

        @if ($user->hasVerifiedPhone())
            <div class="rounded-2xl border border-forest-200 bg-forest-50 p-6">
                <p class="font-semibold text-forest-700">✓ Phone verified — you can book rides.</p>
                <p class="mt-1 text-sm text-ink-600">Complete Level 1 (workplace ID) to unlock the full economy.</p>
            </div>
        @else
            <div class="rounded-2xl border border-ink-200 bg-white p-6">
                <h2 class="font-heading font-semibold text-ink-900">Step 1 — request a code</h2>
                <p class="mt-1 text-sm text-ink-500">We'll text you a 6-digit code. No code inbox? It's in your app log until an SMS provider is connected.</p>

                <form method="POST" action="{{ route('verification.phone.send') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                        placeholder="0803 000 0000"
                        class="w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @error('phone')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Send code
                    </button>
                </form>
            </div>

            <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-6">
                <h2 class="font-heading font-semibold text-ink-900">Step 2 — enter the code</h2>

                <form method="POST" action="{{ route('verification.phone.verify') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="text" name="code" pattern="[0-9]{6}" maxlength="6" inputmode="numeric" placeholder="123456"
                        class="w-full rounded-xl border border-ink-300 px-4 py-2.5 font-mono text-sm tracking-widest focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @error('code')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Verify
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection
