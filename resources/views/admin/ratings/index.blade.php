@extends('layouts.admin')

@section('title', 'Ratings & driver scores')

@section('page', 'Ratings & driver scores')

@section('content')
    <div class="rounded-2xl border border-ink-200 bg-white p-6">
        <h2 class="font-heading font-semibold text-ink-900">Driver scoreboard</h2>
        <p class="mt-1 text-xs text-ink-500">Average rating received on completed trips (guide §8 driver scores).</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-ink-100 text-left text-xs uppercase tracking-wider text-ink-400">
                        <th class="pb-2 pr-4">Driver</th>
                        <th class="pb-2 pr-4">Level</th>
                        <th class="pb-2 pr-4">Workplace</th>
                        <th class="pb-2 pr-4">Rating</th>
                        <th class="pb-2">Reviews</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($scoreboard as $driver)
                        <tr>
                            <td class="py-3 pr-4 font-medium text-ink-900">{{ $driver->name }}</td>
                            <td class="py-3 pr-4 text-ink-600">L{{ $driver->verification_level->value }}</td>
                            <td class="py-3 pr-4 text-ink-600">{{ $driver->workplace?->name ?? '—' }}</td>
                            <td class="py-3 pr-4">
                                <span class="font-mono font-semibold text-forest-700">★ {{ number_format((float) $driver->rating_avg, 2) }}</span>
                            </td>
                            <td class="py-3 text-ink-600">{{ $driver->rating_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-sm text-ink-500">No ratings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <h2 class="font-heading font-semibold text-ink-900">Recent ratings</h2>
            <span class="text-xs text-ink-500">Latest 50</span>
        </div>
        <div class="mt-4 space-y-3">
            @forelse ($ratings as $rating)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-ink-100 bg-paper px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink-800">
                            {{ $rating->rater?->name }} <span class="text-ink-400">→</span> {{ $rating->ratee?->name }}
                        </p>
                        <p class="truncate text-xs text-ink-500">
                            {{ $rating->booking?->trip?->route_name }}
                            @if ($rating->note)
                                · “{{ $rating->note }}”
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm font-semibold text-gold-600">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $rating->rating ? 'text-gold-400' : 'text-ink-200' }}">★</span>
                            @endfor
                        </span>
                        <span class="text-xs text-ink-400">{{ $rating->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-500">No ratings yet.</p>
            @endforelse
        </div>
    </div>
@endsection
