@extends('layouts.admin')

@section('title', 'Community Trust')

@section('page', 'Community Trust')

@section('content')
    <div class="flex items-center justify-between">
        <p class="max-w-xl text-sm text-ink-500">
            The auditable float behind Time-Bank "ride now, drive later" and the 15% profit
            share (guide §2.1). Every movement carries an idempotent reference and a running
            balance; this report rebuilds each balance from the entries and flags any drift.
        </p>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.trust.pay-it-forward') }}" class="rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-paper">Pay-it-forward →</a>
            <a href="{{ route('admin.trust.export') }}" class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">Export CSV →</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Trust balance</p>
            <p class="mt-1 font-mono text-2xl font-bold text-ink-900">₦{{ number_format($total, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ count($entries) }} ledger entries</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Float issued</p>
            <p class="mt-1 font-mono text-2xl font-bold text-forest-700">₦{{ number_format($floatIssued, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Time-Bank credits funded by the Trust</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Float released</p>
            <p class="mt-1 font-mono text-2xl font-bold text-ink-900">₦{{ number_format($floatReleased, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Repaid seats returned to the Trust</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Float outstanding</p>
            <p class="mt-1 font-mono text-2xl font-bold text-gold-700">₦{{ number_format($floatIssued - $floatReleased, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Seats still owed to the community</p>
        </div>
    </div>

    @if (count($mismatchReferences) > 0)
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <p class="font-heading text-sm font-semibold text-rose-700">Reconciliation needs review</p>
            <p class="mt-1 text-sm text-rose-600">
                {{ count($mismatchReferences) }} stored running balance(s) drifted from a rebuild:
                {{ implode(', ', array_slice($mismatchReferences, 0, 6)) }}{{ count($mismatchReferences) > 6 ? ' …' : '' }}.
                Audit these rows before the board review.
            </p>
        </div>
    @else
        <div class="mt-6 rounded-2xl border border-forest-200 bg-forest-50 p-5">
            <p class="font-heading text-sm font-semibold text-forest-700">Ledger balanced</p>
            <p class="mt-1 text-sm text-forest-600">
                Every stored <span class="font-mono">balance_after</span> matches a from-scratch rebuild
                of the same entries — nothing drifted.
            </p>
        </div>
    @endif

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Balance by fund</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-ink-100">
                    <thead>
                        <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                            <th class="px-5 py-3">Fund</th>
                            <th class="px-5 py-3 text-right">Credits</th>
                            <th class="px-5 py-3 text-right">Debits</th>
                            <th class="px-5 py-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($byType as $row)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-ink-900">{{ $row['type']->label() }}</td>
                                <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">₦{{ number_format($row['credits'], 2) }}</td>
                                <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">₦{{ number_format($row['debits'], 2) }}</td>
                                <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-ink-900">₦{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-ink-500">
                                    No Trust movements yet. Time-Bank ride credits and the 15% profit share appear here.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="flex items-center justify-between border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Recent movements</h2>
                <p class="text-xs text-ink-400">{{ $entries->count() }} total</p>
            </div>
            <div class="max-h-96 overflow-auto">
                <table class="min-w-full divide-y divide-ink-100">
                    <thead>
                        <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                            <th class="px-5 py-3">Reference</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3 text-right">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($entries->reverse() as $entry)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-mono text-xs font-semibold text-ink-900">{{ $entry->reference }}</p>
                                    <p class="text-xs text-ink-500">
                                        {{ $entry->type->label() }} ·
                                        <span class="{{ $entry->direction->value === 'credit' ? 'text-forest-600' : 'text-rose-600' }}">{{ $entry->direction->label() }}</span>
                                    </p>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-sm font-semibold {{ $entry->direction->value === 'credit' ? 'text-forest-700' : 'text-ink-900' }}">
                                    {{ $entry->direction->value === 'credit' ? '+' : '−' }}₦{{ number_format((float) $entry->amount, 2) }}
                                </td>
                                <td class="px-5 py-3 text-right text-xs text-ink-500">{{ $entry->recorded_at->format('j M, H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-12 text-center text-sm text-ink-500">No movements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
