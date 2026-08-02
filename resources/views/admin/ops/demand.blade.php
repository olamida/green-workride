@extends('layouts.admin')

@section('title', 'Demand Research')

@section('page', 'Demand Research')

@section('content')
    <div class="flex items-center justify-between">
        <p class="max-w-xl text-sm text-ink-500">
            BRT pre-design demand (guide §9B) — manual junction counts, OD surveys, rider
            check-ins and probe dwell points. Circles sized by people counted, per junction.
        </p>
        <a href="{{ route('admin.forecasts.index') }}" class="rounded-xl border border-ink-200 px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
            Demand calendar →
        </a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">People counted</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">{{ number_format($totals['people_counted']) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Pending check-ins</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $totals['pending_checkins'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">OD matrix rows</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $totals['od_rows'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Probe points</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $totals['probe_points'] }}</p>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Junction demand — {{ today()->format('l, j M') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Junction</th>
                        <th class="px-5 py-3">Corridor</th>
                        <th class="px-5 py-3">Surveys</th>
                        <th class="px-5 py-3">People counted</th>
                        <th class="px-5 py-3">Top destinations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($junctions as $junction)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $junction['name'] }}</p>
                                <p class="text-xs text-ink-500">{{ $junction['zone'] }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-ink-700">{{ str_replace('_', ' ', $junction['corridor']) }}</td>
                            <td class="px-5 py-4 font-mono text-sm text-ink-700">{{ $junction['surveys'] }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-forest-50 px-2.5 py-1 font-mono text-sm font-semibold text-forest-700">
                                    {{ number_format($junction['count']) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-ink-600">
                                @forelse ($junction['destinations'] as $destination => $count)
                                    <span class="mr-2">{{ $destination }} <span class="font-mono text-ink-400">×{{ $count }}</span></span>
                                @empty
                                    <span class="text-ink-400">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-ink-500">
                                No junction counts yet. Put 3 interns at Berger, Banex and Kubwa Junction — day 1 demand.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Pending rider check-ins</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($checkIns as $request)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-ink-900">
                                {{ $request->user->name }}
                                <span class="ml-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $request->passengers_count }} pax</span>
                            </p>
                            <p class="text-xs text-ink-400">{{ $request->requested_at->diffForHumans() }}</p>
                        </div>
                        <p class="mt-1 text-xs text-ink-600">
                            "I'm at <span class="font-medium">{{ $request->pickup_lat }}, {{ $request->pickup_lng }}</span>, need a ride to
                            <span class="font-medium">{{ $request->destination_text }}</span>"
                        </p>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-ink-500">No pending check-ins. Enable the rider check-in to collect supply demand.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Origin → Destination matrix</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-ink-100">
                    <thead>
                        <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                            <th class="px-5 py-3">From</th>
                            <th class="px-5 py-3">To</th>
                            <th class="px-5 py-3 text-right">Trips</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($odMatrix as $row)
                            <tr>
                                <td class="px-5 py-3 text-sm text-ink-900">{{ $row->origin_area }}</td>
                                <td class="px-5 py-3 text-sm text-ink-700">{{ $row->destination_area }}</td>
                                <td class="px-5 py-3 text-right font-mono text-sm font-semibold text-forest-700">{{ number_format($row->count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-ink-500">Collect OD surveys to populate routes.txt demand.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
