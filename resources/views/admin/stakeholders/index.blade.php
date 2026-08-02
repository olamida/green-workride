@extends('layouts.admin')

@section('title', 'Stakeholders')

@section('page', 'Stakeholder Remittances')

@section('content')
    <div class="flex items-center justify-between">
        <p class="max-w-xl text-sm text-ink-500">
            Guide §10 — every paid ride remits the union's share (fare × commission).
            Never fight NURTW/RTEAN: their park is the official hub, they get paid.
        </p>
        <form method="POST" action="{{ route('admin.stakeholders.settle') }}">
            @csrf
            <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">Settle due →</button>
        </form>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Unions</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">{{ $unions->count() }}</p>
        </div>
        <div class="rounded-2xl border border-amber-50 bg-amber-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-amber-600">Pending remittances</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-amber-700">₦{{ number_format($totals['pending_amount']) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Paid out</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">₦{{ number_format($totals['paid_amount']) }}</p>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Union chapters</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Union / park</th>
                        <th class="px-5 py-3">Corridor</th>
                        <th class="px-5 py-3 text-right">Commission</th>
                        <th class="px-5 py-3 text-right">Remittances</th>
                        <th class="px-5 py-3 text-right">Pending</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($unions as $union)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $union->name }}</p>
                                <p class="text-xs text-ink-500">{{ $union->park_location }} · {{ $union->contact_name }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-700">{{ str_replace('_', ' ', $union->corridor ?? 'all') }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $union->commission_rate * 100 }}%</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $union->remittance_count }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-amber-700">₦{{ number_format($union->pending_total ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-ink-500">No union chapters. Add NURTW Kubwa/Berger + RTEAN Lugbe to start remitting.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Recent remittances</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Trip</th>
                        <th class="px-5 py-3">Union</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($remittances as $remittance)
                        <tr>
                            <td class="px-5 py-4 font-mono text-xs text-ink-700">{{ $remittance->reference }}</td>
                            <td class="px-5 py-4 text-xs text-ink-700">#{{ $remittance->trip_id }} {{ $remittance->trip?->route_name }}</td>
                            <td class="px-5 py-4 text-xs text-ink-700">{{ $remittance->union->name }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-ink-900">₦{{ number_format($remittance->amount, 2) }}</td>
                            <td class="px-5 py-4 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $remittance->status->value === 'paid' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $remittance->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-ink-500">No remittances yet. Complete paid trips on a corridor with a union chapter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
