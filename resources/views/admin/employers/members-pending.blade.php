@extends('layouts.admin')

@section('title', 'Pending approvals')

@section('page', 'Employers')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-semibold text-ink-900">Pending approvals</h1>
            <p class="mt-1 text-sm text-ink-500">
                Staff who self-requested to join an employer program (guide §7 Form 1).
                Approving also grants Level 1 workplace verification.
            </p>
        </div>
        <a href="{{ route('admin.employers.index') }}" class="text-sm font-medium text-forest-600 hover:underline">← All employers</a>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Staff</th>
                        <th class="px-5 py-3">Employer</th>
                        <th class="px-5 py-3">Requested</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($members as $member)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $member->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $member->user?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-700">{{ $member->employer?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-xs text-ink-500">{{ $member->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.employers.members.approve', $member) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-forest-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.employers.members.reject', $member) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-ink-50">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-ink-500">Nothing waiting for approval.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
