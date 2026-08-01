@extends('layouts.admin')

@section('title', 'GTFS Publisher')

@section('page', 'GTFS Publisher')

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Last generated</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">
                {{ $meta?->last_generated_at?->diffForHumans() ?? 'Never' }}
            </p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Stops / Routes / Trips</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">
                {{ number_format($meta?->stops_count ?? 0) }} / {{ number_format($meta?->routes_count ?? 0) }} / {{ number_format($meta?->trips_count ?? 0) }}
            </p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Feed size</p>
            <p class="mt-2 font-mono text-lg font-semibold text-ink-900">
                {{ $meta ? number_format(($meta->file_size ?? 0) / 1024, 1).' KB' : '—' }}
            </p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Generate the feed</h2>
            <p class="mt-1 text-sm text-ink-500">
                Regenerates <code class="rounded bg-paper px-1 font-mono text-xs">agency/stops/routes/trips/stop_times/calendar/shapes</code>
                from live trips + the stop catalog and zips them. Runs nightly at 02:00 and on every trip publish.
            </p>

            <form method="POST" action="{{ route('admin.gtfs.regenerate') }}" class="mt-4">
                @csrf
                <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Regenerate feed →
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Submit to Google</h2>
            <p class="mt-1 text-sm text-ink-500">
                Host the static feed, validate it at
                <code class="rounded bg-paper px-1 font-mono text-xs">feedvalidator.mobilitydata.org</code>,
                then apply to the Google Transit Partner Program.
            </p>

            <div class="mt-4 space-y-3">
                <a href="{{ $feedUrl }}" class="inline-flex items-center gap-2 rounded-xl border border-forest-300 bg-forest-50 px-4 py-2.5 text-sm font-semibold text-forest-700 transition hover:bg-forest-100">
                    ⤓ Download gtfs.zip
                </a>
                @if ($feedExists)
                    <p class="text-xs text-ink-500">
                        Feed hash <code class="font-mono">{{ substr($meta?->feed_hash ?? '', 0, 12) }}…</code> ·
                        Vehicle positions: <code class="font-mono">/gtfs/gtfs-rt/vehicle_positions.pb</code>
                    </p>
                @else
                    <p class="text-xs text-amber-600">Feed not generated yet — click regenerate.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Feed endpoints</h2>
        </div>
        <div class="divide-y divide-ink-100">
            <div class="flex items-center justify-between px-6 py-4">
                <div>
                    <p class="text-sm font-medium text-ink-900">Static GTFS</p>
                    <p class="font-mono text-xs text-ink-500">GET /gtfs/gtfs.zip</p>
                </div>
                <a href="{{ $feedUrl }}" class="text-xs font-semibold text-forest-600 hover:underline">Open</a>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <div>
                    <p class="text-sm font-medium text-ink-900">Vehicle positions (GTFS-RT)</p>
                    <p class="font-mono text-xs text-ink-500">GET /gtfs/gtfs-rt/vehicle_positions.pb</p>
                </div>
                <a href="{{ route('gtfs.vehicle_positions') }}" class="text-xs font-semibold text-forest-600 hover:underline">Open</a>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <div>
                    <p class="text-sm font-medium text-ink-900">Trip updates (GTFS-RT)</p>
                    <p class="font-mono text-xs text-ink-500">GET /gtfs/gtfs-rt/trip_updates.pb</p>
                </div>
                <a href="{{ route('gtfs.trip_updates') }}" class="text-xs font-semibold text-forest-600 hover:underline">Open</a>
            </div>
        </div>
    </div>
@endsection
