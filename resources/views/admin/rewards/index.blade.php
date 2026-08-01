@extends('layouts.admin')

@section('title', 'Rewards')

@section('page', 'Rewards')

@section('content')
    <div class="flex items-center justify-between">
        <p class="max-w-xl text-sm text-ink-500">
            Sponsor-funded incentive campaigns (guide §2.2 #7 + §6 Workflow 2). The engine awards
            claims automatically when a trigger fires — every payout is an idempotent, auditable claim.
        </p>
        <a href="{{ route('admin.rewards.create') }}" class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            New campaign →
        </a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Campaigns</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">{{ $campaigns->count() }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Claims issued</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $campaigns->sum('claims_count') }}</p>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Campaigns</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Campaign</th>
                        <th class="px-5 py-3">Trigger</th>
                        <th class="px-5 py-3">Reward</th>
                        <th class="px-5 py-3">Period</th>
                        <th class="px-5 py-3">Audience</th>
                        <th class="px-5 py-3 text-right">Claims</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $campaign->name }}</p>
                                <p class="text-xs text-ink-500">{{ $campaign->sponsor_name ?: 'WorkRide Community' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-700">{{ str_replace('_', ' ', $campaign->trigger->value) }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-forest-700">{{ $campaign->reward_value }} {{ str_replace('_', ' ', $campaign->reward_type->value) }}</td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-500">{{ $campaign->period->value }}</td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-500">{{ $campaign->audience?->value ?? 'all' }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $campaign->claims_count }}</td>
                            <td class="px-5 py-4 text-right">
                                <form method="POST" action="{{ route('admin.rewards.toggle', $campaign) }}">
                                    @csrf
                                    <button class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $campaign->active ? 'bg-green-50 text-green-700' : 'bg-ink-50 text-ink-500' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $campaign->active ? 'bg-green-600' : 'bg-ink-400' }}"></span>
                                        {{ $campaign->active ? 'Active' : 'Paused' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-ink-500">
                                No campaigns yet. Create one and riders/drivers start earning.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Recent claims</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Campaign</th>
                        <th class="px-5 py-3 text-right">Value</th>
                        <th class="px-5 py-3 text-right">Awarded</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($claims as $claim)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $claim->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $claim->user?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-700">{{ $claim->campaign?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-forest-700">{{ $claim->reward_value }} {{ str_replace('_', ' ', $claim->reward_type) }}</td>
                            <td class="px-5 py-4 text-right text-xs text-ink-500">{{ $claim->awarded_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-ink-500">No claims yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
