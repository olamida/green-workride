@extends('layouts.app')

@section('title', 'My Commutes')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-ink-900">My commutes</h1>
            <p class="mt-1 text-sm text-ink-500">
                Save a route once, republish it with one tap. Publishing still goes through the
                fixed anti-surge fare — a template is a pre-filled form, never a shortcut around it.
            </p>
        </div>
        <a href="{{ route('trips.create') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
            + Publish a new trip
        </a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-forest-200 bg-forest-50 p-4 text-sm text-forest-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $enabled)
        <div class="rounded-2xl border border-ink-200 bg-white px-6 py-12 text-center">
            <p class="font-heading text-lg font-semibold text-ink-900">Trip templates are disabled</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-ink-500">
                Set <code class="rounded bg-ink-100 px-1.5 py-0.5 font-mono text-xs">FEATURE_TRIP_TEMPLATES=true</code>
                in your environment to let drivers save and republish one-tap commutes.
            </p>
        </div>
    @else
        <div class="mb-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading text-sm font-semibold text-ink-900">New commute</h2>
                <span class="text-xs text-ink-400">Save the route first, publish it any morning with one tap</span>
            </div>
            <form method="POST" action="{{ route('templates.store') }}" class="grid gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div>
                    <label for="template_name" class="mb-1 block text-xs font-medium text-ink-700">Name</label>
                    <input id="template_name" name="name" type="text" maxlength="255" value="{{ old('name') }}" placeholder="e.g. Morning Kubwa run"
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                </div>
                <div>
                    <label for="template_corridor" class="mb-1 block text-xs font-medium text-ink-700">Which road</label>
                    <select id="template_corridor" name="corridor" required
                            class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                        @foreach (\App\Enums\Corridor::cases() as $option)
                            <option value="{{ $option->value }}" @selected(old('corridor') === $option->value)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="template_time" class="mb-1 block text-xs font-medium text-ink-700">Departure</label>
                    <input id="template_time" name="departure_time" type="time" required value="{{ old('departure_time', '07:00') }}"
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                </div>
                <div>
                    <label for="template_seats" class="mb-1 block text-xs font-medium text-ink-700">Seats</label>
                    <input id="template_seats" name="total_seats" type="number" min="1" max="60" required value="{{ old('total_seats', 4) }}"
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                </div>
                <div class="sm:col-span-2">
                    <label for="template_origin" class="mb-1 block text-xs font-medium text-ink-700">Origin</label>
                    <input id="template_origin" name="origin_text" type="text" maxlength="255" required value="{{ old('origin_text') }}" placeholder="e.g. Kubwa Junction"
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                </div>
                <div class="sm:col-span-2">
                    <label for="template_destination" class="mb-1 block text-xs font-medium text-ink-700">Destination</label>
                    <input id="template_destination" name="destination_text" type="text" maxlength="255" required value="{{ old('destination_text') }}" placeholder="e.g. Federal Secretariat"
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                </div>
                <div class="sm:col-span-2">
                    <p class="mb-1 text-xs font-medium text-ink-700">Runs on</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([['mon', 'Mon'], ['tue', 'Tue'], ['wed', 'Wed'], ['thu', 'Thu'], ['fri', 'Fri'], ['sat', 'Sat'], ['sun', 'Sun']] as [$day, $label])
                            <label class="flex items-center gap-1.5 rounded-lg border border-ink-200 px-2.5 py-1 text-xs">
                                <input type="checkbox" name="days[]" value="{{ $day }}" @checked(in_array($day, old('days', []), true) || in_array($day, ['mon', 'tue', 'wed', 'thu', 'fri'], true))
                                       class="h-3.5 w-3.5 rounded border-ink-300 text-forest-600 focus:ring-forest-500">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-[11px] text-ink-400">Leave all unticked to run every day.</p>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_free_volunteer" value="1" @checked(old('is_free_volunteer'))
                               class="h-4 w-4 rounded border-ink-300 text-forest-600 focus:ring-forest-500">
                        <span class="text-xs font-medium text-ink-700">Free volunteer ride</span>
                    </label>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Save commute
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($templates as $template)
            <div class="rounded-2xl border border-ink-200 bg-white p-5" data-template-card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-forest-50 px-2.5 py-0.5 text-xs font-semibold text-forest-700">{{ $template->corridor?->short() }}</span>
                            @if ($template->is_free_volunteer)
                                <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">FREE volunteer</span>
                            @endif
                            @if ($template->women_only)
                                <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Women-only</span>
                            @endif
                            @if (! $template->is_active)
                                <span class="rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-600">Paused</span>
                            @endif
                        </div>
                        <p class="mt-2 font-heading text-lg font-semibold text-ink-900">{{ $template->name }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ $template->routeTitle() }}</p>
                    </div>

                    <div class="text-right">
                        <p class="font-mono text-lg font-semibold text-ink-900">
                            {{ $template->is_free_volunteer ? 'FREE' : '₦'.number_format((float) $template->fare_per_seat, 0) }}
                        </p>
                        <p class="text-xs text-ink-500">fixed per road</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-ink-100 pt-4 text-sm text-ink-600">
                    <span>⏰ {{ $template->departure_time }} · {{ $template->daysLabel() }}</span>
                    <span>🪑 {{ $template->total_seats }} seats</span>
                    @if ($template->vehicle)
                        <span>🚌 {{ $template->vehicle->make }} {{ $template->vehicle->model }} · {{ $template->vehicle->plate_number }}</span>
                    @endif
                    <span class="text-xs text-ink-400">used {{ $template->times_used }}×</span>
                </div>

                @php
                    $next = $template->nextDeparture();
                @endphp
                <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-4">
                    <form method="POST" action="{{ route('templates.publish', $template) }}" class="inline">
                        @csrf
                        <button type="submit" @disabled(! $next || ! $template->is_active)
                                class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700 disabled:cursor-not-allowed disabled:opacity-50"
                                title="{{ $next ? 'Publish the '.$next->format('D j M · g:i A').' departure' : 'No upcoming run day' }}">
                            Publish today
                        </button>
                    </form>
                    <form method="POST" action="{{ route('templates.publish-week', $template) }}" class="inline">
                        @csrf
                        <button type="submit" @disabled(! $template->is_active)
                                class="rounded-xl border border-forest-600 px-4 py-2 text-sm font-semibold text-forest-700 transition hover:bg-forest-50 disabled:cursor-not-allowed disabled:opacity-50">
                            Publish this week
                        </button>
                    </form>
                    <form method="POST" action="{{ route('templates.destroy', $template) }}" class="inline"
                          onsubmit="return confirm('Delete this saved commute?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-xl px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            Delete
                        </button>
                    </form>
                    @if ($next)
                        <span class="ml-auto text-xs font-medium text-forest-700">
                            Next run: {{ $next->format('D j M · g:i A') }}
                        </span>
                    @else
                        <span class="ml-auto text-xs text-ink-400">No upcoming run day</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-ink-200 bg-white px-6 py-12 text-center">
                <p class="font-heading text-lg font-semibold text-ink-900">No saved commutes yet</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-ink-500">
                    Publish a trip and tick "Save as a template" — your daily Kubwa → CBD run becomes a
                    one-tap publish for tomorrow.
                </p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('trips.create') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Publish a trip
                    </a>
                    <a href="{{ route('trips.index') }}" class="rounded-xl border border-forest-600 px-4 py-2 text-sm font-semibold text-forest-700 transition hover:bg-forest-50">
                        Browse the trip board
                    </a>
                </div>
            </div>
        @endforelse
    </div>
@endsection
