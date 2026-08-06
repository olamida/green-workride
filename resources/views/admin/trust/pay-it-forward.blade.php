@extends('layouts.admin')

@section('title', 'Pay-it-forward — '.$month->format('F Y'))

@section('page', 'Community Trust')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-semibold text-ink-900">Pay-it-forward statement</h1>
            <p class="mt-1 text-sm text-ink-500">
                Who rode on Time-Bank, who repaid, who is overdue, who was waived — {{ $month->format('F Y') }}.
            </p>
        </div>
        <form method="GET" action="{{ route('admin.trust.pay-it-forward') }}" class="flex items-center gap-2">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                class="rounded-xl border border-ink-300 bg-white px-3 py-2 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            <button class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">View →</button>
            <a href="{{ route('admin.trust.pay-it-forward.export', ['month' => $month->format('Y-m')]) }}"
                class="rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700 transition hover:bg-paper">CSV ↓</a>
        </form>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Riders (float issued)</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($rode) }}</p>
            <p class="mt-1 text-xs text-ink-500">₦{{ number_format($floatIssued, 2) }} extended by the Trust</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Seats owed / repaid</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">{{ $seatsRepaid }} / {{ $seatsOwed }}</p>
            <p class="mt-1 text-xs text-ink-500">₦{{ number_format($floatReleased, 2) }} float released</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Outstanding balance</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-gold-700">₦{{ number_format(max($fareValue - $floatReleased, 0), 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">fare value still to repay</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Overdue</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-red-600">{{ $byStatus['overdue'] }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ $byStatus['waived'] }} waived · {{ $byStatus['owed'] }} still open</p>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="flex items-center justify-between border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Who rode &amp; repaid</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Rider</th>
                        <th class="px-5 py-3 text-right">Rides</th>
                        <th class="px-5 py-3 text-right">Seats owed</th>
                        <th class="px-5 py-3 text-right">Seats repaid</th>
                        <th class="px-5 py-3 text-right">Fare value</th>
                        <th class="px-5 py-3 text-right">Repaid</th>
                        <th class="px-5 py-3 text-right">Overdue</th>
                        <th class="px-5 py-3 text-right">Waived</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($perUser as $row)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $row['name'] }}</p>
                                <p class="text-xs text-ink-500">{{ $row['email'] }}</p>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $row['credits'] }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $row['seats_owed'] }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-forest-700">{{ $row['seats_repaid'] }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-ink-900">₦{{ number_format($row['fare_value'], 2) }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $row['repaid'] }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-red-600">{{ $row['overdue'] }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-500">{{ $row['waived'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-ink-500">
                                No ride credits in {{ $month->format('F Y') }}. The Time-Bank float has not moved this month.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($credits->isNotEmpty())
        <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Individual credits</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-ink-100">
                    <thead>
                        <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                            <th class="px-5 py-3">When</th>
                            <th class="px-5 py-3">Rider</th>
                            <th class="px-5 py-3">Route</th>
                            <th class="px-5 py-3 text-right">Seats</th>
                            <th class="px-5 py-3 text-right">Fare</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($credits as $credit)
                            <tr>
                                <td class="px-5 py-4 text-xs text-ink-500">{{ $credit->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-5 py-4 text-sm text-ink-900">{{ $credit->user?->name ?? 'Unknown' }}</td>
                                <td class="px-5 py-4 text-xs text-ink-500">
                                    {{ $credit->trip?->route_name ?? ($credit->trip ? ($credit->trip->origin_text.' → '.$credit->trip->destination_text) : '—') }}
                                </td>
                                <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $credit->seats_repaid }}/{{ $credit->seats_owed }}</td>
                                <td class="px-5 py-4 text-right font-mono text-sm text-ink-900">₦{{ number_format((float) $credit->fare_value, 2) }}</td>
                                <td class="px-5 py-4"><x-badge :status="$credit->status->value" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
