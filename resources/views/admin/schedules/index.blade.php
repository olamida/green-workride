@extends('layouts.admin')

@section('title', 'Recurring Schedules')

@section('page', 'Recurring Schedules')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-xl text-sm text-ink-500">
            Guide §6 Workflow 5 — declare the guaranteed timetable ("Kubwa→CBD every 15 min,
            Mon–Fri 06:30–09:00") once and the nightly <code class="rounded bg-ink-100 px-1 font-mono text-xs">GenerateRecurringTripsJob</code>
            materialises real, bookable trips for today and tomorrow. Use <strong>Materialise</strong> to
            create today's trips immediately without waiting for the cron.
        </p>
        <a href="{{ route('admin.schedules.create') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700">
            New schedule →
        </a>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-forest-200 bg-forest-50 px-5 py-4 text-sm text-forest-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Route</th>
                        <th class="px-5 py-3">Window</th>
                        <th class="px-5 py-3">Frequency</th>
                        <th class="px-5 py-3">Days</th>
                        <th class="px-5 py-3">Driver / Vehicle</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td class="px-5 py-4">
                                <span class="text-sm font-medium text-ink-900">{{ $schedule->routeLabel() }}</span>
                                <span class="block text-xs text-ink-400">{{ $schedule->corridor()?->label() ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-4 font-mono text-sm text-ink-700">
                                {{ $schedule->departure_time }}
                                @if ($schedule->end_time)
                                    → {{ $schedule->end_time }}
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-700">
                                @if ($schedule->end_time)
                                    Every {{ $schedule->frequency_minutes }} min
                                @else
                                    Single run
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach (($schedule->days_of_week ?? []) as $day)
                                        <span class="rounded bg-ink-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-ink-600">{{ $day }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-ink-700">
                                <span class="block font-medium text-ink-900">{{ $schedule->driver?->name }}</span>
                                <span class="text-ink-400">{{ $schedule->vehicle?->plate_number }} · {{ $schedule->vehicle?->seats }} seats</span>
                            </td>
                            <td class="px-5 py-4">
                                <x-badge :tone="$schedule->isActive() ? 'success' : 'neutral'">
                                    {{ $schedule->isActive() ? 'Active' : 'Paused' }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.schedules.materialize', $schedule) }}" onsubmit="return confirm('Materialise today\'s trips now?')">
                                        @csrf
                                        <button class="rounded-lg border border-ink-200 px-2.5 py-1 text-xs font-semibold text-ink-700 hover:bg-paper">Materialise</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.schedules.toggle', $schedule) }}">
                                        @csrf
                                        <button class="rounded-lg border border-ink-200 px-2.5 py-1 text-xs font-semibold text-ink-700 hover:bg-paper">
                                            {{ $schedule->isActive() ? 'Pause' : 'Resume' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule? Existing materialised trips stay.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-ink-500">
                                No recurring schedules yet. Create one and the nightly job starts materialising
                                guaranteed trips — the supply backbone of the corridor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
