@extends('layouts.admin')

@section('title', 'Members — '.$employer->name)

@section('page', 'Employers')

@section('content')
    <a href="{{ route('admin.employers.show', $employer) }}" class="text-sm font-medium text-forest-600 hover:underline">← {{ $employer->name }}</a>

    <div class="mt-3 flex items-center justify-between gap-4">
        <h1 class="font-heading text-2xl font-semibold text-ink-900">Members</h1>
        <span class="text-sm text-ink-500">{{ $employer->members->count() }} total</span>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Staff</th>
                        <th class="px-5 py-3">Employee ID</th>
                        <th class="px-5 py-3">Joined via</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($employer->members->sortByDesc('created_at') as $member)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $member->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $member->user?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-ink-500">{{ $member->employee_id ?: '—' }}</td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-500">{{ $member->joined_via?->value ?? 'employer' }}</td>
                            <td class="px-5 py-4">
                                <x-badge :status="$member->status->value" />
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($member->isPending())
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
                                    @else
                                        <form method="POST" action="{{ route('admin.employers.members.review', $member) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-ink-50">Re-open</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-ink-500">No members yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
