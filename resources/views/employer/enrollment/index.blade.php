@extends('layouts.app')

@section('title', 'Employer Roster - {{ $employer->name }}')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-heading text-2xl font-bold text-ink-900">{{ $employer->name }}</h1>
        <p class="mt-1 text-sm text-ink-500">Manage your staff roster — upload CSV to enroll or update members.</p>
    </div>

    <a href="{{ route('employers.self') }}" class="rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 transition hover:border-forest-300">
        ← Back to My Employers
    </a>
</div>

<div class="grid gap-5 sm:grid-cols-4">
    <div class="rounded-2xl border border-ink-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Total Members</p>
        <p class="mt-2 font-mono text-3xl font-semibold text-ink-900">{{ $stats['total'] }}</p>
    </div>

    <div class="rounded-2xl border border-forest-200 bg-forest-50 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-forest-700">Active</p>
        <p class="mt-2 font-mono text-3xl font-semibold text-forest-800">{{ $stats['active'] }}</p>
    </div>

    <div class="rounded-2xl border border-gold-200 bg-amber-50 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-amber-700">Pending</p>
        <p class="mt-2 font-mono text-3xl font-semibold text-amber-800">{{ $stats['pending'] }}</p>
    </div>

    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-blue-700">Admins</p>
        <p class="mt-2 font-mono text-3xl font-semibold text-blue-800">{{ $stats['admins'] }}</p>
    </div>
</div>

<div class="mt-6 rounded-2xl border border-ink-200 bg-white p-5">
    <h2 class="font-heading font-semibold text-ink-900">Upload Staff CSV</h2>
    <p class="mt-1 text-sm text-ink-500">Columns: <code class="text-xs bg-paper px-1.5 py-0.5 rounded">email</code>, <code class="text-xs bg-paper px-1.5 py-0.5 rounded">name</code> (optional), <code class="text-xs bg-paper px-1.5 py-0.5 rounded">phone</code> (optional), <code class="text-xs bg-paper px-1.5 py-0.5 rounded">employee_id</code> (optional). Header row auto-detected.</p>

    <form method="POST" action="{{ route('employer.enrollment.store', $employer) }}" class="mt-4" enctype="multipart/form-data">
        @csrf
        <div class="flex flex-wrap items-center gap-4">
            <label class="flex items-center gap-2 rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 cursor-pointer hover:border-forest-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-forest-600">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <span>Choose CSV file</span>
                <input type="file" name="csv" accept=".csv,.txt" class="sr-only" required>
            </label>

            <button type="submit" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                Enroll Staff
            </button>
        </div>

        @error('csv')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </form>
</div>

<div class="mt-6 rounded-2xl border border-ink-200 bg-white p-5">
    <div class="flex items-center justify-between">
        <h2 class="font-heading font-semibold text-ink-900">Current Roster</h2>
        <span class="text-sm text-ink-500">{{ $employer->members->count() }} member(s)</span>
    </div>

    @if ($employer->members->isEmpty())
        <p class="mt-4 text-center text-sm text-ink-500">No members yet. Upload a CSV to enroll staff.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-ink-200 text-left text-xs font-semibold uppercase tracking-wider text-ink-400">
                        <th class="pb-3 pr-4">Name</th>
                        <th class="pb-3 pr-4">Email</th>
                        <th class="pb-3 pr-4">Employee ID</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3 pr-4">Joined Via</th>
                        <th class="pb-3 pr-4">Admin</th>
                        <th class="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach ($employer->members as $member)
                        <tr class="hover:bg-paper/50">
                            <td class="py-3 pr-4 font-medium text-ink-900">{{ $member->user->name }}</td>
                            <td class="py-3 pr-4 text-ink-600">{{ $member->user->email }}</td>
                            <td class="py-3 pr-4 text-ink-500">{{ $member->employee_id ?? '—' }}</td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @if ($member->isActive()) bg-forest-100 text-forest-700
                                    @elseif ($member->isPending()) bg-gold-100 text-gold-700
                                    @else bg-ink-100 text-ink-600 @endif">
                                    {{ $member->status->label() }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-ink-500 capitalize">{{ $member->joined_via->label() }}</td>
                            <td class="py-3 pr-4">
                                @if ($member->is_employer_admin)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        </svg>
                                        Admin
                                    </span>
                                @else
                                    <span class="text-ink-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2">
                                    @if (! $member->is_employer_admin)
                                        <form method="POST" action="{{ route('employer.enrollment.toggle-admin', [$employer, $member]) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-blue-600 bg-white px-3 py-1 text-xs font-medium text-blue-700 transition hover:bg-blue-50" title="Make employer admin">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('employer.enrollment.toggle-admin', [$employer, $member]) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-ink-300 bg-white px-3 py-1 text-xs font-medium text-ink-600 transition hover:bg-ink-50" title="Remove admin">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('employer.enrollment.destroy', [$employer, $member]) }}" class="inline" onsubmit="return confirm('Remove {{ $member->user->name }} from the roster?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-300 bg-white px-3 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50" title="Remove member">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                        </form>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection