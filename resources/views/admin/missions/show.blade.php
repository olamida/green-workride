@extends('layouts.admin')

@section('title', $mission->name)

@section('page', 'Missions')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.missions.index') }}" class="text-sm font-medium text-forest-600 hover:underline">← All missions</a>
            <h1 class="mt-1 font-heading text-xl font-semibold text-ink-900">{{ $mission->name }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $mission->activity_type->label() }} ·
                {{ number_format($mission->reward_value) }} {{ str_replace('_', ' ', $mission->reward_type->value) }} ·
                {{ $mission->verification_mode->value }} ·
                {{ $mission->participants_count }} participants
            </p>
        </div>
        <div class="flex gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold
                {{ $mission->status->value === 'published' ? 'bg-green-50 text-green-700' : 'bg-ink-50 text-ink-500' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $mission->status->value === 'published' ? 'bg-green-600' : 'bg-ink-400' }}"></span>
                {{ $mission->status->value === 'published' ? 'Live' : 'Draft' }}
            </span>
            <form method="POST" action="{{ route('admin.missions.toggle', $mission) }}">
                @csrf
                <button class="rounded-xl border border-ink-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
                    {{ $mission->status->value === 'published' ? 'Pause' : 'Publish' }}
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Goal</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ $mission->metric_goal }}</p>
            <p class="mt-1 text-xs text-ink-500">events within {{ $mission->metric_window_days }} days</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Budget</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">₦{{ number_format($mission->budget_spent, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">of ₦{{ number_format($mission->budget_total ?? 0, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Window</p>
            <p class="mt-2 text-sm font-semibold text-ink-900">
                {{ $mission->starts_at?->format('M j, Y') ?? 'now' }} → {{ $mission->ends_at?->format('M j, Y') ?? 'open' }}
            </p>
        </div>
    </div>

    @if ($mission->description)
        <p class="mt-6 max-w-2xl text-sm text-ink-600">{{ $mission->description }}</p>
    @endif

    @if ($mission->verification_mode->value === 'proof' && $submissions->isNotEmpty())
        <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Proof submissions</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($submissions as $submission)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                        <div class="flex items-center gap-4">
                            @if ($submission->proof_photo_path)
                                <img src="{{ asset('storage/'.$submission->proof_photo_path) }}" alt="Proof"
                                    class="h-16 w-16 rounded-xl border border-ink-100 object-cover">
                            @endif
                            <div>
                                <p class="text-sm font-medium text-ink-900">{{ $submission->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $submission->note ?: 'No note' }} · {{ $submission->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($submission->status->value === 'pending')
                                <form method="POST" action="{{ route('admin.missions.approve', $submission) }}">
                                    @csrf
                                    <button class="rounded-xl bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">Approve & pay</button>
                                </form>
                                <form method="POST" action="{{ route('admin.missions.reject', $submission) }}">
                                    @csrf
                                    <button class="rounded-xl border border-ink-300 bg-white px-3 py-1.5 text-xs font-semibold text-ink-600 transition hover:bg-ink-50">Reject</button>
                                </form>
                            @else
                                <span class="rounded-full px-3 py-1.5 text-xs font-semibold
                                    {{ $submission->status->value === 'approved' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    {{ $submission->status->value }} {{ $submission->reward_awarded ? '· paid' : '' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-ink-500">No submissions yet.</p>
                @endforelse
            </div>
        </div>
    @endif

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Top participants</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Member</th>
                        <th class="px-5 py-3 text-right">Count</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($participants as $p)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $p->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $p->user?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $p->metric_count }} / {{ $mission->metric_goal }}</td>
                            <td class="px-5 py-4 text-right text-xs capitalize text-ink-500">{{ $p->status->value }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-sm text-ink-500">No participants yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
