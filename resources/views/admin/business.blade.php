@extends('layouts.admin')

@section('title', 'Business Dashboard')

@section('page', 'Business Dashboard')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-500">
            Funding-ready ledger: gross revenue, MRR, commission, subsidy utilization — everything a CIC board or angel investor asks for.
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.business.export.transactions') }}" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-ink-800">
                ⤓ Transactions CSV
            </a>
            <a href="{{ route('admin.business.export.settlements') }}" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-ink-800">
                ⤓ Settlements CSV
            </a>
            <a href="{{ route('admin.business.export.subsidy') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                ⤓ Subsidy utilization CSV
            </a>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Gross revenue</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">₦{{ number_format($stats['gross_revenue'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ number_format($stats['paid_rides']) }} paid rides captured</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">MRR (this month)</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">₦{{ number_format($stats['mrr'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Captured fares since {{ now()->startOfMonth()->format('d M') }}</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Driver earnings</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">₦{{ number_format($stats['driver_earnings'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Earned balance credited to drivers</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Subsidy issued</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-gold-700">₦{{ number_format($stats['subsidy_issued'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">₦{{ number_format($stats['subsidy_spent'], 2) }} spent · ₦{{ number_format($stats['subsidy_remaining'], 2) }} remaining</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Commission earned</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">₦{{ number_format($stats['commission'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ config('workride.commission_rate') * 100 }}% of gross</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Union fees</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">₦{{ number_format($stats['union_fees'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ config('workride.union_fee_rate') * 100 }}% NURTW/RTEAN</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Insurance covered</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">₦{{ number_format($stats['insurance'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">₦{{ number_format(config('workride.insurance_per_trip'), 2) }}/ride</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">P2P fees + payouts</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">₦{{ number_format($stats['p2p_fees'], 2) }} / ₦{{ number_format($stats['payouts'], 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Transfer fees · withdrawals paid out</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Revenue per day — last 14 days</h2>
            </div>
            <div class="p-6">
                @php
                    $max = max(array_column($revenueByDay, 'total'), [0])[0];
                    $barW = 100 / count($revenueByDay);
                @endphp
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-40 w-full rounded-lg bg-paper">
                    @foreach ($revenueByDay as $i => $point)
                        @php $h = $max > 0 ? ($point['total'] / $max) * 92 : 1; @endphp
                        <rect x="{{ $i * $barW + 2 }}" y="{{ 96 - $h }}" width="{{ $barW - 4 }}" height="{{ max(1, $h) }}" rx="2" fill="#2e7d32" opacity="0.9">
                            <title>₦{{ number_format($point['total']) }} — {{ $point['day'] }}</title>
                        </rect>
                    @endforeach
                </svg>
                <div class="mt-2 flex gap-1.5">
                    @foreach ($revenueByDay as $point)
                        <div class="flex-1 text-center text-[10px] text-ink-400">{{ $point['day'] }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Trips by corridor</h2>
            </div>
            <div class="p-6 space-y-3">
                @forelse ($tripsByCorridor as $row)
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-paper px-3 py-1 text-xs font-semibold text-ink-700">{{ $row['corridor'] }}</span>
                        <span class="font-mono text-sm text-ink-900">{{ number_format($row['total']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No trips published yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="flex items-center justify-between border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Subsidy utilization per workplace (MDA audit)</h2>
            <span class="text-xs text-ink-400">{{ count($subsidyByWorkplace) }} workplaces funded</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-100 bg-paper text-xs uppercase tracking-wider text-ink-400">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Workplace</th>
                        <th class="px-6 py-3 font-semibold">Staff funded</th>
                        <th class="px-6 py-3 font-semibold">Issued</th>
                        <th class="px-6 py-3 font-semibold">Spent</th>
                        <th class="px-6 py-3 font-semibold">Utilization</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($subsidyByWorkplace as $row)
                        <tr>
                            <td class="px-6 py-3 font-medium text-ink-800">{{ $row['workplace'] }}</td>
                            <td class="px-6 py-3 text-ink-600">{{ number_format($row['staff_funded']) }}</td>
                            <td class="px-6 py-3 font-mono text-ink-900">₦{{ number_format($row['issued'], 2) }}</td>
                            <td class="px-6 py-3 font-mono text-ink-900">₦{{ number_format($row['spent'], 2) }}</td>
                            <td class="px-6 py-3">
                                @php $pct = $row['issued'] > 0 ? ($row['spent'] / $row['issued']) * 100 : 0; @endphp
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $pct >= 80 ? 'bg-forest-50 text-forest-700' : ($pct >= 40 ? 'bg-gold-50 text-gold-700' : 'bg-red-50 text-red-600') }}">
                                    {{ number_format($pct, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-ink-500">No subsidy credits issued yet — bulk credit via the Subsidies page.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
