@extends('layouts.app')

@section('title', 'Connect guide · '.$trip->route_name)

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <a href="{{ route('trips.show', $trip) }}" class="text-sm font-medium text-forest-600 hover:text-forest-700">
            &larr; Back to trip
        </a>

        <div class="mt-4 flex flex-col gap-1">
            <h1 class="font-heading text-2xl font-bold tracking-tight text-ink-900">
                Connect guide
            </h1>
            <p class="text-sm text-ink-600">
                {{ $trip->route_name }} · {{ $trip->corridor->label() }} ·
                departs {{ $trip->departure_time->format('g:i A') }}
            </p>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div id="connect-guide-map" class="h-[460px] w-full overflow-hidden rounded-2xl border border-ink-200"
                     style="z-index: 1;"></div>

                <div data-guide-banner class="mt-4 rounded-2xl border border-forest-200 bg-white p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Walking distance
                            </p>
                            <p data-guide-distance class="mt-1 font-mono text-2xl font-semibold text-ink-900">
                                {{ $target['lat'] !== null ? '—' : 'n/a' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                ETA to boarding
                            </p>
                            <p data-guide-eta class="mt-1 font-mono text-2xl font-semibold text-ink-900">
                                {{ $target['lat'] !== null ? '—' : 'n/a' }}
                            </p>
                        </div>
                        <div class="w-full">
                            <p data-guide-status class="text-sm font-medium text-ink-700">
                                {{ $target['type'] === 'none' ? 'Waiting for the driver to share a location…' : 'Pin the vehicle on the map, then walk to the green dot.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-ink-200 bg-white p-5">
                    <h2 class="font-heading font-semibold text-ink-900">Meet the ride</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500">Boarding point</dt>
                            <dd class="font-medium text-ink-900">{{ $target['label'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500">Driver</dt>
                            <dd class="font-medium text-ink-900">{{ $trip->driver->name }}</dd>
                        </div>
                        @if ($trip->vehicle)
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-500">Vehicle</dt>
                                <dd class="font-medium text-ink-900">{{ $trip->vehicle->plate_number }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500">Seats left</dt>
                            <dd class="font-medium text-ink-900">{{ $trip->available_seats }} / {{ $trip->total_seats }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gold-200 bg-gold-50/60 p-5">
                    <h2 class="font-heading font-semibold text-ink-900">How the guide works</h2>
                    <ol class="mt-3 space-y-2 text-sm text-ink-700">
                        <li class="flex gap-2"><span class="font-semibold text-forest-600">1.</span> The green dot is where to meet the vehicle.</li>
                        <li class="flex gap-2"><span class="font-semibold text-forest-600">2.</span> Your position is drawn in blue as you move.</li>
                        <li class="flex gap-2"><span class="font-semibold text-forest-600">3.</span> Distance and ETA update live as the vehicle approaches.</li>
                    </ol>
                    <p class="mt-3 text-xs text-ink-500">
                        Your live position is only shown to you and other participants on this ride. It is never broadcast publicly.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/connect-guide.js'])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initConnectGuide(
                document.getElementById('connect-guide-map'),
                @json($config),
                @json($target)
            );
        });
    </script>
@endsection
