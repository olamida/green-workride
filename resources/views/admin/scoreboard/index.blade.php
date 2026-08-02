@extends('layouts.admin')

@section('title', 'Driver Scoreboard')

@section('page', 'Driver Scores')

@section('content')
    <div class="flex items-center justify-between">
        <p class="max-w-xl text-sm text-ink-500">
            Weekly 0-100 snapshot (guide §8 governance) — rides, ratings, punctuality,
            pothole reports and green points. Level bands: Bronze / Silver / Gold / Platinum.
        </p>
        <form method="POST" action="{{ route('admin.scoreboard.run') }}">
            @csrf
            <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">Run weekly job →</button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="flex items-center justify-between border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Scoreboard</h2>
            <p class="text-xs text-ink-400">{{ $latest ? 'Latest period: '.$latest->format('j M Y') : 'No scores yet' }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Driver</th>
                        <th class="px-5 py-3 text-right">Rides</th>
                        <th class="px-5 py-3 text-right">Rating</th>
                        <th class="px-5 py-3 text-right">Pothole reports</th>
                        <th class="px-5 py-3 text-right">Score</th>
                        <th class="px-5 py-3 text-right">Level</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($scores as $score)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $score->user?->name ?? 'Deleted user' }}</p>
                                <p class="text-xs text-ink-500">{{ $score->period_start->format('j M') }} → {{ $score->period_end->format('j M Y') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $score->rides_completed }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ number_format($score->rating_avg, 1) }} ★</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $score->pothole_reports }}</td>
                            <td class="px-5 py-4 text-right font-mono text-lg font-bold text-ink-900">{{ $score->score }}</td>
                            <td class="px-5 py-4 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $score->level->value === 'platinum' ? 'bg-indigo-50 text-indigo-700'
                                       : ($score->level->value === 'gold' ? 'bg-amber-50 text-amber-700'
                                       : ($score->level->value === 'silver' ? 'bg-slate-100 text-slate-700' : 'bg-orange-50 text-orange-700')) }}">
                                    {{ $score->level->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-ink-500">
                                No scores yet. Run the weekly job after some completed trips with ratings.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
