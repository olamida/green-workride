@extends('layouts.admin')

@section('title', 'Verifications')

@section('page', 'Verifications')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex gap-2">
            <a href="{{ route('admin.verifications.index') }}"
                @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('status') ? 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' : 'bg-ink-900 text-white'])>
                All ({{ $counts['pending'] + $counts['pending_manual_review'] + $counts['approved'] + $counts['rejected'] }})
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'pending']) }}"
                @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('status') === 'pending' ? 'bg-gold-500 text-ink-900' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'])>
                Pending ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'pending_manual_review']) }}"
                @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('status') === 'pending_manual_review' ? 'bg-gold-700 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'])>
                Needs review ({{ $counts['pending_manual_review'] }})
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'approved']) }}"
                @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('status') === 'approved' ? 'bg-forest-600 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'])>
                Approved ({{ $counts['approved'] }})
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'rejected']) }}"
                @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('status') === 'rejected' ? 'bg-red-600 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'])>
                Rejected ({{ $counts['rejected'] }})
            </a>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.verifications.index', ['type' => 'workplace_id']) }}"
                @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('type') === 'workplace_id' ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'])>
                Workplace ID
            </a>
            <a href="{{ route('admin.verifications.index', ['type' => 'nin']) }}"
                @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('type') === 'nin' ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'])>
                NIN
            </a>
        </div>
    </div>

    @if ($costs['identitypass'] > 0 || $costs['smile'] > 0)
        <div class="mb-6 flex flex-wrap gap-3">
            @if ($costs['identitypass'] > 0)
                <div class="rounded-2xl border border-ink-200 bg-white px-4 py-3 text-sm">
                    <span class="text-ink-500">IdentityPass (NIN) this month:</span>
                    <span class="font-semibold text-ink-900">₦{{ number_format($costs['identitypass'], 2) }}</span>
                </div>
            @endif
            @if ($costs['smile'] > 0)
                <div class="rounded-2xl border border-ink-200 bg-white px-4 py-3 text-sm">
                    <span class="text-ink-500">Smile Identity (drivers) this month:</span>
                    <span class="font-semibold text-ink-900">₦{{ number_format($costs['smile'], 2) }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Details</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Reviewed by</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($verifications as $verification)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $verification->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $verification->user?->email }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-700">{{ \Str::title(str_replace('_', ' ', $verification->type)) }}</td>
                            <td class="px-5 py-4 text-sm text-ink-500">
                                @if ($verification->type === 'nin')
                                    NIN •••• {{ $verification->nin_last4 }}
                                @elseif ($verification->workplace)
                                    {{ $verification->workplace->name }}
                                    <span class="ml-1 rounded-full bg-ink-100 px-2 py-0.5 text-xs text-ink-500">{{ $verification->workplace->acronym }}</span>
                                @endif

                                @if ($verification->provider || $verification->tier || $verification->liveness_score !== null)
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        @if ($verification->tier)
                                            <span class="rounded-full bg-ink-900 px-2 py-0.5 text-[10px] font-semibold text-white">T{{ $verification->tier->value }}</span>
                                        @endif
                                        @if ($verification->provider)
                                            <span class="rounded-full bg-ink-100 px-2 py-0.5 text-[10px] font-medium text-ink-500">{{ $verification->provider->label() }}</span>
                                        @endif
                                        @if ($verification->liveness_score !== null)
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                $verification->liveness_score >= 80 ? 'bg-forest-100 text-forest-700' : ($verification->liveness_score >= 75 ? 'bg-gold-100 text-gold-800' : 'bg-red-100 text-red-700'),
                                            ])>
                                                liveness {{ $verification->liveness_score }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4"><x-badge :status="$verification->status" /></td>
                            <td class="px-5 py-4 text-sm text-ink-500">
                                {{ $verification->reviewer?->name ?? '—' }}
                                @if ($verification->admin_note)
                                    <p class="mt-0.5 text-xs text-ink-400">{{ $verification->admin_note }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if (in_array($verification->status, ['pending', 'pending_manual_review'], true))
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.verifications.approve', $verification) }}">
                                            @csrf
                                            <button class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">Approve</button>
                                        </form>
                                        <details class="relative">
                                            <summary class="cursor-pointer list-none rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Reject</summary>
                                            <form method="POST" action="{{ route('admin.verifications.reject', $verification) }}"
                                                class="absolute right-0 top-full z-10 mt-2 w-72 rounded-2xl border border-ink-200 bg-white p-4 shadow-xl">
                                                @csrf
                                                <textarea name="note" rows="2" required
                                                    class="w-full rounded-xl border border-ink-300 px-3 py-2 text-sm focus:border-red-400 focus:ring-2 focus:ring-red-100"
                                                    placeholder="Reason for rejection (required)"></textarea>
                                                <button class="mt-2 w-full rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">Confirm reject</button>
                                            </form>
                                        </details>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-ink-500">No verifications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $verifications->withQueryString()->links() }}</div>
@endsection
