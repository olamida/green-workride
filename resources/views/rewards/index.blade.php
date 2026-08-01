@extends('layouts.app')

@section('title', 'Rewards')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Rewards & Green Points</h1>
                <p class="mt-1 text-sm text-ink-500">Drive, ride and report potholes — sponsors pay you back.</p>
            </div>
        </div>

        @if (! $enabled)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Rewards are not enabled yet in this environment. When live, Green Points convert straight into wallet cash.
            </div>
        @endif

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Green Points</p>
                <p class="mt-2 font-mono text-3xl font-semibold text-forest-700">{{ number_format($greenPoints) }}</p>
                <p class="mt-1 text-xs text-ink-500">≈ ₦{{ number_format($greenPoints * $rate, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Redemption rate</p>
                <p class="mt-2 font-mono text-3xl font-semibold text-ink-900">₦{{ number_format($rate, 0) }}</p>
                <p class="mt-1 text-xs text-ink-500">per point</p>
            </div>
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Minimum redeem</p>
                <p class="mt-2 font-mono text-3xl font-semibold text-ink-900">{{ number_format($minRedeem) }}</p>
                <p class="mt-1 text-xs text-ink-500">points</p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Redeem for cash</h2>
            <p class="mt-1 text-sm text-ink-500">Green Points convert to wallet cash at ₦{{ number_format($rate, 0) }}/point. Subsidy credits are ride-only and never touch this.</p>
            <form method="POST" action="{{ route('rewards.redeem') }}" class="mt-4 flex max-w-md gap-3">
                @csrf
                <input type="number" name="points" min="{{ $minRedeem }}" step="{{ $minRedeem }}" placeholder="Points" required
                    class="flex-1 rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Redeem →
                </button>
            </form>
            @error('points')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Active campaigns</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($campaigns as $campaign)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ $campaign->name }}</p>
                            <p class="text-xs text-ink-500">
                                {{ $campaign->description ?: 'Auto-awarded — no claim needed.' }}
                            </p>
                        </div>
                        <span class="rounded-full bg-paper px-3 py-1.5 text-xs font-semibold text-forest-700">
                            {{ $campaign->reward_value }} {{ str_replace('_', ' ', $campaign->reward_type->value) }}
                        </span>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-ink-500">No active campaigns right now.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Your rewards</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-ink-100">
                    <thead>
                        <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                            <th class="px-5 py-3">Campaign</th>
                            <th class="px-5 py-3 text-right">Value</th>
                            <th class="px-5 py-3 text-right">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($claims as $claim)
                            <tr>
                                <td class="px-5 py-4 text-sm text-ink-900">{{ $claim->campaign?->name ?? 'Green Points' }}</td>
                                <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-forest-700">{{ $claim->reward_value }} {{ str_replace('_', ' ', $claim->reward_type) }}</td>
                                <td class="px-5 py-4 text-right text-xs text-ink-500">{{ $claim->awarded_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-ink-500">Nothing yet. Complete rides and report potholes to start earning.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
