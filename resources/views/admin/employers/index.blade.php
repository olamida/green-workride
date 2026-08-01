@extends('layouts.admin')

@section('title', 'Employers')

@section('page', 'Employers')

@section('content')
    <div class="flex items-center justify-between">
        <p class="max-w-xl text-sm text-ink-500">
            Corporate Mobility Programs (guide §2.2 #2/#4). Employers fund a prepaid wallet; the
            engine covers a defined share of each staff commute with a full audit trail.
        </p>
        <a href="{{ route('admin.employers.create') }}" class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            Register employer →
        </a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Employers</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">{{ $employers->count() }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Enrolled staff</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $employers->sum('members_count') }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Prepaid balances</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">₦{{ number_format($employers->sum(fn ($e) => (float) ($e->wallet?->cash_balance ?? 0)), 2) }}</p>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Mobility programs</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Employer</th>
                        <th class="px-5 py-3">Program</th>
                        <th class="px-5 py-3">Corridors</th>
                        <th class="px-5 py-3">Staff</th>
                        <th class="px-5 py-3 text-right">Wallet</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($employers as $employer)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.employers.show', $employer) }}" class="text-sm font-medium text-forest-700 hover:underline">
                                    {{ $employer->name }}
                                </a>
                                <p class="text-xs text-ink-500">{{ $employer->zone ?: '—' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-paper px-2.5 py-1 text-xs font-medium capitalize text-ink-700">{{ $employer->program_type->label() }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs text-ink-500">
                                {{ $employer->corridors ? implode(', ', array_map('strtoupper', $employer->corridors)) : 'All' }}
                            </td>
                            <td class="px-5 py-4 font-mono text-sm text-ink-700">{{ $employer->members_count }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-forest-700">
                                ₦{{ number_format((float) ($employer->wallet?->cash_balance ?? 0), 2) }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if ($employer->active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-ink-50 px-2.5 py-1 text-xs font-semibold text-ink-500">
                                        Paused
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-ink-500">
                                No employers yet. Register your first Corporate Mobility Program to start covering staff commutes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
