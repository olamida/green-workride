@extends('layouts.app')

@section('title', 'Impact')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-ink-900">Your community impact</h1>
            <p class="mt-1 text-sm text-ink-500">
                Every shared ride cuts CO₂, saves fuel and keeps a colleague in the workforce.
                Download your certificates below for ESG reporting and subsidy audits.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('impact.certificate', 'co2') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                CO₂ certificate
            </a>
            <a href="{{ route('impact.certificate', 'fuel') }}" class="rounded-xl border border-forest-600 px-4 py-2 text-sm font-semibold text-forest-700 transition hover:bg-forest-50">
                Fuel saved
            </a>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">CO₂ saved</p>
            <p class="mt-2 font-mono text-2xl font-bold text-forest-700">{{ number_format((float) $personal->co2_saved_kg, 1) }} kg</p>
            <p class="mt-1 text-xs text-ink-500">≈ {{ number_format((float) $personal->trees_equivalent, 1) }} trees offset</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Fuel saved</p>
            <p class="mt-2 font-mono text-2xl font-bold text-ink-900">{{ number_format((float) $personal->fuel_saved_litres, 1) }} L</p>
            <p class="mt-1 text-xs text-ink-500">at ₦1,400/L ≈ ₦{{ number_format((float) $personal->fuel_saved_litres * 1400, 0) }} saved</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Shared trips</p>
            <p class="mt-2 font-mono text-2xl font-bold text-ink-900">{{ $personal->total_trips }}</p>
            <p class="mt-1 text-xs text-ink-500">Green level {{ $personal->level }} / 5</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Tree equivalent</p>
            <p class="mt-2 font-mono text-2xl font-bold text-forest-700">{{ number_format((float) $personal->trees_equivalent, 1) }} 🌳</p>
            <p class="mt-1 text-xs text-ink-500">your share of the forest</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Workplace leaderboard</h2>
            <p class="mt-1 text-sm text-ink-500">
                @if (auth()->user()->workplace)
                    {{ auth()->user()->workplace->name }}
                @else
                    Attach a workplace to compete with your colleagues.
                @endif
            </p>
            <div class="mt-4 space-y-3">
                @forelse ($workplaceLeaderboard as $index => $entry)
                    <div class="flex items-center gap-3 rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <span class="font-mono text-sm font-bold {{ $index === 0 ? 'text-forest-700' : 'text-ink-400' }}">#{{ $index + 1 }}</span>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-ink-800">{{ $entry->name }}</p>
                        </div>
                        <span class="font-mono text-sm font-semibold text-forest-700">{{ number_format((float) $entry->impactStat->co2_saved_kg, 1) }} kg</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No colleagues are sharing rides yet. Be the first.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Abuja-wide leaderboard</h2>
            <p class="mt-1 text-sm text-ink-500">Top 25 verified civil servants by CO₂ saved.</p>
            <div class="mt-4 space-y-3">
                @forelse ($leaderboard as $index => $entry)
                    <div class="flex items-center gap-3 rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <span class="font-mono text-sm font-bold {{ $index < 3 ? 'text-forest-700' : 'text-ink-400' }}">#{{ $index + 1 }}</span>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-ink-800">{{ $entry->name }}</p>
                            <p class="text-xs text-ink-500">{{ $entry->workplace?->acronym ?? 'Independent' }}</p>
                        </div>
                        <span class="font-mono text-sm font-semibold text-forest-700">{{ number_format((float) $entry->impactStat->co2_saved_kg, 1) }} kg</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No verified riders have impact data yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
