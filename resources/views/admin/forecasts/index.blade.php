@extends('layouts.admin')

@section('title', 'Demand Calendar')

@section('page', 'Demand Forecasting')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-xl text-sm text-ink-500">
            Guide §9 — Abuja demand follows religion, government cycles (FAAC/FEC/salary week),
            festivals, weather and fuel scarcity. Log events; the app suggests extra vehicles so
            we never deploy empty buses.
        </p>
        <form method="POST" action="{{ route('admin.forecasts.train') }}">
            @csrf
            <button class="rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700 hover:bg-paper">Train on history (14 days) →</button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="flex items-center justify-between border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Learned predictions <span class="text-xs font-normal text-ink-400">(Phase 2 — trained on boarded/completed booking history, nightly)</span></h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Corridor</th>
                        <th class="px-5 py-3 text-right">Predicted rides</th>
                        <th class="px-5 py-3 text-right">Baseline</th>
                        <th class="px-5 py-3 text-right">Peak hour</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($learned as $row)
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-ink-900">{{ $row['date'] }} <span class="text-xs text-ink-400">{{ $row['day_name'] }}</span></td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-700">{{ str_replace('_', ' ', $row['corridor_label']) }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-forest-700">{{ number_format($row['predicted'], 1) }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ number_format($row['baseline'], 1) }}</td>
                            <td class="px-5 py-4 text-right font-mono text-sm text-ink-700">{{ $row['peak_hour'] }}:00</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-ink-500">
                                No learned predictions yet. Run "Train on history" (or wait for the nightly job) and the table will
                                project demand from your booking history.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Log a demand event</h2>
        </div>
        <form method="POST" action="{{ route('admin.forecasts.store') }}" class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <div>
                <label class="text-xs font-medium text-ink-500">Date</label>
                <input type="date" name="date" required value="{{ old('date', now()->addDays(1)->toDateString()) }}" class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-500">Event type</label>
                <select name="event_type" required class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2 text-sm">
                    @foreach (\App\Enums\ForecastEventType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-500">Corridor</label>
                <select name="corridor" required class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2 text-sm">
                    @foreach (\App\Enums\Corridor::cases() as $corridor)
                        <option value="{{ $corridor->value }}">{{ $corridor->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-ink-500">Event name</label>
                <input type="text" name="event_name" required placeholder="Salary week — FAAC payment" value="{{ old('event_name') }}" class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-500">Multiplier <span class="text-ink-400">(blank = auto)</span></label>
                <input type="number" name="expected_demand_multiplier" step="0.1" min="0.1" max="3" placeholder="1.6" value="{{ old('expected_demand_multiplier') }}" class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-3">
                <label class="text-xs font-medium text-ink-500">Notes</label>
                <input type="text" name="notes" placeholder="e.g. Reduce CBD trips after 2:30pm" value="{{ old('notes') }}" class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-3">
                <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">Log event →</button>
            </div>
        </form>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Upcoming demand events</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Event</th>
                        <th class="px-5 py-3">Corridor</th>
                        <th class="px-5 py-3 text-right">Multiplier</th>
                        <th class="px-5 py-3 text-right">Extra vehicles</th>
                        <th class="px-5 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-ink-900">{{ $event['date'] }} <span class="text-xs text-ink-400">{{ $event['day_name'] }}</span></td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-ink-900">{{ $event['event_name'] }}</p>
                                <p class="text-xs text-ink-500">{{ $event['event_type'] }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs capitalize text-ink-700">{{ str_replace('_', ' ', $event['corridor']) }}</td>
                            <td class="px-5 py-4 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-1 font-mono text-sm font-semibold {{ $event['multiplier'] >= 1 ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                    ×{{ number_format($event['multiplier'], 1) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-sm font-semibold text-forest-700">{{ $event['extra_vehicles'] }}</td>
                            <td class="px-5 py-4 text-xs text-ink-600">{{ $event['notes'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-ink-500">No events logged. Add FAAC week + Juma'a and watch the calendar plan itself.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
