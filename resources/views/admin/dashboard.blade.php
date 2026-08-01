@extends('layouts.admin')

@section('title', 'Overview')

@section('page', 'Overview')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Users</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($stats['users']) }}</p>
            <p class="mt-1 text-xs text-ink-500"><span class="font-medium text-red-600">{{ $stats['banned_users'] }}</span> banned</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Pending verifications</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($stats['pending_verifications']) }}</p>
            <a href="{{ route('admin.verifications.index', ['status' => 'pending']) }}" class="mt-1 inline-block text-xs font-medium text-forest-600 hover:underline">Review queue →</a>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Workplaces</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($stats['workplaces']) }}</p>
            <p class="mt-1 text-xs text-ink-500">45 FCT MDAs seeded</p>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Trips today</p>
            <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">{{ number_format($stats['trips_today']) }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ number_format($stats['bookings']) }} bookings all-time</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-ink-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Subsidy credits issued</p>
        <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">₦{{ number_format($stats['subsidy_issued'], 2) }}</p>
        <p class="mt-1 text-xs text-ink-500">Trackable palliative — wallet dual balance</p>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-heading font-semibold text-ink-900">Verification queue</h2>
                <a href="{{ route('admin.verifications.index') }}" class="text-xs font-semibold text-forest-600 hover:underline">All →</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($recentVerifications as $verification)
                    <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-800">{{ $verification->user?->name ?? 'Unknown user' }}</p>
                            <p class="text-xs text-ink-500">
                                {{ \Str::title(str_replace('_', ' ', $verification->type)) }}
                                @if ($verification->type === 'nin')
                                    · NIN •••• {{ $verification->nin_last4 }}
                                @elseif ($verification->workplace)
                                    · {{ $verification->workplace->name }}
                                @endif
                            </p>
                        </div>
                        <x-badge :status="$verification->status" />
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No verification submissions.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-heading font-semibold text-ink-900">Newest users</h2>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-forest-600 hover:underline">All →</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($recentUsers as $user)
                    <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-800">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink-500">{{ $user->workplace?->name ?? 'No workplace' }}</p>
                        </div>
                        <span class="font-mono text-xs font-medium text-forest-700">L{{ $user->verification_level->value }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No users yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
