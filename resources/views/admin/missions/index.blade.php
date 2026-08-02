@extends('layouts.admin')

@section('title', 'Missions')

@section('page', 'Missions')

@section('content')
    <div class="flex items-center justify-between">
        <p class="max-w-xl text-sm text-ink-500">
            Promoted volunteer activities (guide §9B demand + §8 stakeholder). A promoter defines an
            activity + reward; the app observes real events and pays out automatically (auto) or after
            photo-proof review. Every payout is an idempotent, auditable ledger entry.
        </p>
        <a href="{{ route('admin.missions.create') }}" class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            New mission →
        </a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Missions</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">{{ $missions->count() }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Participants</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $missions->sum('participants_count') }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Proof submissions</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $missions->sum('submissions_count') }}</p>
        </div>
        <div class="rounded-2xl border border-amber-50 bg-amber-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-amber-600">Pending review</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-amber-700">{{ $pending }}</p>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Missions</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Mission</th>
                        <th class="px-5 py-3">Activity</th>
                        <th class="px-5 py-3">Reward</th>
                        <th class="px-5 py-3">Verification</th>
                        <th class="px-5 py-3 text-right">Participants</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($missions as $mission)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.missions.show', $mission) }}" class="text-sm font-medium text-forest-700 hover:underline">{{ $mission->name }}</a>
                                <p class="text-xs text-ink-500">{{ $mission->sponsor_name ?: 'WorkRide Community' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-ink-700">{{ $mission->activity_type->label() }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-forest-700">{{ number_format($mission->reward_value) }} {{ str_replace('_', ' ', $mission->reward_type->value) }}</td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-500">{{ $mission->verification_mode->value }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $mission->participants_count }}</td>
                            <td class="px-5 py-4 text-right">
                                <form method="POST" action="{{ route('admin.missions.toggle', $mission) }}">
                                    @csrf
                                    <button class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $mission->status->value === 'published' ? 'bg-green-50 text-green-700' : 'bg-ink-50 text-ink-500' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $mission->status->value === 'published' ? 'bg-green-600' : 'bg-ink-400' }}"></span>
                                        {{ $mission->status->value === 'published' ? 'Live' : 'Draft' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-ink-500">
                                No missions yet. Create one and the app starts observing real events.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
