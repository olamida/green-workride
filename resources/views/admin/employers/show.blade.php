@extends('layouts.admin')

@section('title', $employer->name)

@section('page', 'Employers')

@section('content')
    <a href="{{ route('admin.employers.index') }}" class="text-sm font-medium text-forest-600 hover:underline">← All employers</a>

    <div class="mt-3 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-semibold text-ink-900">{{ $employer->name }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $employer->program_type->label() }} · {{ $employer->zone ?: 'no zone' }} ·
                {{ $employer->corridors ? implode(', ', array_map('strtoupper', $employer->corridors)) : 'all corridors' }}
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold
            {{ $employer->active ? 'bg-green-50 text-green-700' : 'bg-ink-50 text-ink-500' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $employer->active ? 'bg-green-600' : 'bg-ink-400' }}"></span>
            {{ $employer->active ? 'Active' : 'Paused' }}
        </span>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Wallet balance</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">₦{{ number_format($stats['balance'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Enrolled staff</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $stats['members'] }} <span class="text-sm font-normal text-ink-400">({{ $stats['active_members'] }} active)</span></p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Trips covered</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($stats['trips_covered']) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Coverage spent</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">₦{{ number_format($stats['coverage_spent'], 2) }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Fund mobility wallet</h2>
            <p class="mt-1 text-sm text-ink-500">Load the prepaid balance that pays for staff commutes. Every spend is audited.</p>
            <form method="POST" action="{{ route('admin.employers.fund', $employer) }}" class="mt-4 flex gap-3">
                @csrf
                <input type="number" name="amount" step="1" min="1" placeholder="₦ amount" required
                    class="flex-1 rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Fund →
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Enroll staff (CSV roster)</h2>
            <p class="mt-1 text-sm text-ink-500">
                Upload a roster with <code class="rounded bg-paper px-1 font-mono text-xs">email,name,phone,employee_id</code>
                columns. Unknown emails are <strong>auto-created</strong> (temporary password sent by email) and get Level 1.
            </p>
            <form method="POST" action="{{ route('admin.employers.enroll', $employer) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <input type="file" name="csv" accept=".csv,.txt" required
                    class="w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-forest-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                @error('csv')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Enroll staff →
                </button>
            </form>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="flex items-center justify-between border-b border-ink-100 px-6 py-4">
            <div>
                <h2 class="font-heading font-semibold text-ink-900">Enrolled staff</h2>
                <a href="{{ route('admin.employers.members', $employer) }}" class="text-xs font-medium text-forest-600 hover:underline">View all members →</a>
            </div>
            @php $pendingCount = $employer->members->where('isPending', true)->count(); @endphp
            @if ($pendingCount > 0)
                <a href="{{ route('admin.employers.members', $employer) }}" class="inline-flex items-center gap-1.5 rounded-full bg-gold-50 px-3 py-1.5 text-xs font-semibold text-gold-700 hover:bg-gold-100">
                    {{ $pendingCount }} pending approval
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Staff</th>
                        <th class="px-5 py-3">Employee ID</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($employer->members->take(8) as $member)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $member->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $member->user?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-ink-500">{{ $member->employee_id ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <x-badge :status="$member->status->value" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-sm text-ink-500">No staff enrolled yet. Upload a roster CSV to start covering commutes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($wallet && $wallet->transactions->isNotEmpty())
        <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Wallet ledger</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-ink-100">
                    <thead>
                        <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                            <th class="px-5 py-3">Reference</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3 text-right">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($wallet->transactions->sortByDesc('created_at') as $transaction)
                            <tr>
                                <td class="px-5 py-4 font-mono text-xs text-ink-500">{{ $transaction->reference }}</td>
                                <td class="px-5 py-4 text-xs capitalize text-ink-700">{{ $transaction->type }}</td>
                                <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-ink-900">₦{{ number_format((float) $transaction->amount, 2) }}</td>
                                <td class="px-5 py-4 text-right text-xs text-ink-500">{{ $transaction->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
