@extends('layouts.admin')

@section('title', 'Fleet')

@section('page', 'Fleet Lifecycle')

@section('content')
    <p class="max-w-xl text-sm text-ink-500">
        Asset-light fleet (guide §11) — leased buses, daily pre-trip inspections,
        faults and preventive maintenance. A failed inspection or open fault grounds
        the asset via the trip-publish gate.
    </p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Assets</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">{{ $assets->count() }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Serviceable</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-green-700">{{ $assets->filter(fn ($a) => $a->isServiceable())->count() }}</p>
        </div>
        <div class="rounded-2xl border border-amber-50 bg-amber-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-amber-600">Open faults</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-amber-700">{{ $openFaults->count() }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Upcoming maintenance</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $upcomingMaintenance->count() }}</p>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Fleet assets</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Asset</th>
                        <th class="px-5 py-3">Corridor</th>
                        <th class="px-5 py-3">Driver</th>
                        <th class="px-5 py-3 text-right">Mileage</th>
                        <th class="px-5 py-3 text-right">Open faults</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($assets as $asset)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $asset->make }} {{ $asset->model }}</p>
                                <p class="text-xs text-ink-500">{{ $asset->plate_number }} · {{ $asset->acquisition_type->label() }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-700">{{ str_replace('_', ' ', $asset->corridor ?? '—') }}</td>
                            <td class="px-5 py-4 text-xs text-ink-700">{{ $asset->assignedDriver?->name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ number_format($asset->mileage) }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm {{ $asset->open_faults > 0 ? 'font-semibold text-amber-700' : 'text-ink-400' }}">{{ $asset->open_faults }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $asset->status->value === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    {{ $asset->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.fleet.inspect', $asset) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="is_passed" class="rounded-lg border border-ink-200 px-2 py-1 text-xs">
                                        <option value="1">Pass</option>
                                        <option value="0">Fail</option>
                                    </select>
                                    <button class="rounded-lg bg-forest-600 px-3 py-1 text-xs font-semibold text-white hover:bg-forest-700">Inspect</button>
                                </form>
                                <form method="POST" action="{{ route('admin.fleet.schedule', $asset) }}" class="mt-2 flex items-center gap-2">
                                    @csrf
                                    <select name="type" class="rounded-lg border border-ink-200 px-2 py-1 text-xs">
                                        <option value="preventive_5000km">5,000 km</option>
                                        <option value="monthly_inspection">Monthly</option>
                                    </select>
                                    <button class="rounded-lg border border-ink-200 px-3 py-1 text-xs font-semibold text-ink-700 hover:bg-ink-50">Schedule</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-ink-500">
                                No fleet assets. Lease 3× 18-seaters to start the Kubwa-CBD pilot (Capex 0).
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
                <h2 class="font-heading font-semibold text-ink-900">Open faults</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($openFaults as $fault)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-ink-900">{{ $fault->asset->make }} {{ $fault->asset->model }} · {{ $fault->asset->plate_number }}</p>
                            <p class="text-xs text-ink-400">{{ $fault->severity }}/5 · {{ $fault->reporter?->name ?? 'system' }}</p>
                        </div>
                        <p class="mt-1 text-xs text-ink-600">{{ $fault->description }}</p>
                        <form method="POST" action="{{ route('admin.faults.resolve', $fault) }}" class="mt-2">
                            @csrf
                            <button class="rounded-lg border border-forest-200 px-3 py-1 text-xs font-semibold text-forest-700 hover:bg-forest-50">Mark fixed</button>
                        </form>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-ink-500">No open faults. Fleet is healthy.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Upcoming maintenance</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($upcomingMaintenance as $job)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ $job->asset->plate_number }} · {{ $job->type->label() }}</p>
                            <p class="text-xs text-ink-500">Due {{ $job->due_date->format('j M Y') }}{{ $job->due_km ? ' · '.number_format($job->due_km).' km' : '' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.maintenance.complete', $job) }}">
                            @csrf
                            <button class="rounded-lg bg-forest-600 px-3 py-1 text-xs font-semibold text-white hover:bg-forest-700">Done</button>
                        </form>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-ink-500">Nothing due.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
