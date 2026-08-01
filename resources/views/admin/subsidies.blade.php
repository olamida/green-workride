@extends('layouts.admin')

@section('title', 'Subsidies')

@section('page', 'Subsidies')

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Subsidy issued</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">₦{{ number_format($stats['subsidy_issued'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Staff funded</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($stats['staff_funded']) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Workplaces</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($stats['workplaces']) }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Bulk credit (CSV)</h2>
            <p class="mt-1 text-sm text-ink-500">
                Upload a CSV with <code class="rounded bg-paper px-1 font-mono text-xs">email,amount</code> rows
                to credit <strong>subsidy_credits</strong> (naira). MDA finance gets a full audit trail.
            </p>

            <form method="POST" action="{{ route('admin.subsidies.credit') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="workplace_id" class="text-sm font-medium text-ink-700">Workplace (optional)</label>
                    <select name="workplace_id" id="workplace_id" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                        <option value="">— All / external —</option>
                        @foreach ($workplaces as $workplace)
                            <option value="{{ $workplace->id }}">{{ $workplace->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="csv" class="text-sm font-medium text-ink-700">CSV file</label>
                    <input type="file" name="csv" id="csv" accept=".csv,.txt" required
                        class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-forest-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @error('csv')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Credit subsidy credits →
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">By workplace</h2>
            <div class="mt-4 space-y-2">
                @forelse ($workplaces as $workplace)
                    <a href="{{ route('admin.subsidies.index', ['workplace_id' => $workplace->id]) }}"
                        class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3 transition hover:border-forest-300">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-800">{{ $workplace->name }}</p>
                            <p class="text-xs text-ink-500">{{ $workplace->users_count }} staff</p>
                        </div>
                        <span class="shrink-0 font-mono text-sm font-semibold text-forest-700">₦{{ number_format((float) $workplace->subsidy_total, 2) }}</span>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">No workplaces yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Recent subsidy transactions</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Staff</th>
                        <th class="px-5 py-3">Workplace</th>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($recent as $transaction)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $transaction->wallet?->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $transaction->wallet?->user?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-500">
                                {{ $transaction->wallet?->user?->workplace?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-ink-500">
                                <a href="{{ route('receipts.subsidy', $transaction) }}" class="font-semibold text-forest-600 hover:underline">{{ $transaction->reference }}</a>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-forest-700">
                                +₦{{ number_format((float) $transaction->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-xs text-ink-500">{{ $transaction->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-ink-500">No subsidy credits yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
