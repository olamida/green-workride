@extends('layouts.admin')

@section('title', 'Road Intelligence')

@section('page', 'Road Intelligence')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Total events</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">{{ number_format($stats['total_events']) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Confirmed potholes</p>
            <p class="mt-2 font-mono text-lg font-semibold text-red-600">{{ number_format($stats['confirmed_potholes']) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Awaiting confirmation</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">{{ number_format($stats['unconfirmed']) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">IRI segments</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">{{ number_format($stats['segments']) }}</p>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-500">
            Confirmed potholes from ≥ {{ config('workride.pothole_confirm.min_reports') }} reports within
            {{ config('workride.pothole_confirm.radius_m') }} m / {{ config('workride.pothole_confirm.within_hours') }} h.
        </p>
        <a href="{{ route('admin.road.export') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
            ⤓ Export CSV for FERMA
        </a>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Segments by condition</h2>
            </div>
            <div class="p-6">
                @forelse ($segmentsByCondition as $row)
                    <div class="mb-3 flex items-center justify-between">
                        <x-badge :status="$row->condition" :label="\Str::title($row->condition)" />
                        <span class="font-mono text-sm text-ink-700">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No road segments computed yet.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Recent events</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($recentEvents as $event)
                    <div class="flex items-center justify-between px-6 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ \Str::title($event->type->label()) }}
                                @if ($event->is_confirmed)
                                    <span class="ml-2 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-600">CONFIRMED</span>
                                @endif
                            </p>
                            <p class="text-xs text-ink-500">
                                {{ $event->road_name ?? 'Unnamed road' }} · sev {{ $event->severity }}/5 ·
                                {{ $event->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="font-mono text-xs text-ink-400">{{ $event->user?->name ?? 'anonymous' }}</span>
                    </div>
                @empty
                    <p class="px-6 py-4 text-sm text-ink-500">No road events collected yet — sensors activate during active trips.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
