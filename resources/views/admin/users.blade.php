@extends('layouts.admin')

@section('title', 'Users')

@section('page', 'Users')

@section('content')
    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or email…"
            class="min-w-64 flex-1 rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        <select name="level" class="rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            <option value="">All levels</option>
            @for ($i = 0; $i <= 3; $i++)
                <option value="{{ $i }}" {{ request('level') !== null && (int) request('level') === $i ? 'selected' : '' }}>
                    Level {{ $i }}
                </option>
            @endfor
        </select>
        <button class="rounded-xl bg-ink-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ink-800">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Workplace</th>
                        <th class="px-5 py-3">Level</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Joined</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($users as $user)
                        <tr @class(['bg-red-50/50' => $user->is_banned])>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $user->name }}</p>
                                <p class="text-xs text-ink-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-500">
                                {{ $user->workplace?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm font-semibold text-ink-900">L{{ $user->verification_level->value }}</span>
                                    <div class="flex w-10 gap-0.5">
                                        @for ($i = 0; $i < 3; $i++)
                                            <span class="h-1.5 flex-1 rounded-full {{ $user->verification_level->value > $i ? 'bg-forest-500' : 'bg-ink-200' }}"></span>
                                        @endfor
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full border border-ink-200 bg-paper px-2.5 py-0.5 text-xs font-medium text-ink-600">
                                    {{ $user->role->label() }}
                                </span>
                                @if ($user->is_banned)
                                    <span class="ml-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Banned</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-500">{{ $user->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-4 text-right">
                                @if (! $user->isAdmin())
                                    @if ($user->is_banned)
                                        <form method="POST" action="{{ route('admin.users.unban', $user) }}" class="inline">
                                            @csrf
                                            <button class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">Unban</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="inline">
                                            @csrf
                                            <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Ban</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-ink-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->withQueryString()->links() }}</div>
@endsection
