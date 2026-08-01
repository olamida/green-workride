@extends('layouts.public')

@section('title', 'Road Intelligence')

@section('content')
    <div class="mb-8">
        <h1 class="font-heading text-2xl font-bold text-ink-900">Road Intelligence</h1>
        <p class="mt-1 text-sm text-ink-500">
            Confirmed potholes reported by verified WorkRide drivers in the last 72 hours.
            Green = low severity · Gold = medium · Red = severe. Road data is open for FERMA.
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div id="road-map" class="h-[520px] w-full" style="z-index: 1;"></div>
    </div>

    @if ($segments->isNotEmpty())
        <div class="mt-8">
            <h2 class="font-heading font-semibold text-ink-900">Worst segments (IRI)</h2>
            <div class="mt-3 overflow-hidden rounded-2xl border border-ink-200 bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-paper text-left text-xs uppercase tracking-wider text-ink-400">
                        <tr>
                            <th class="px-4 py-3">Road</th>
                            <th class="px-4 py-3">Avg IRI</th>
                            <th class="px-4 py-3">Condition</th>
                            <th class="px-4 py-3">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($segments as $segment)
                            <tr>
                                <td class="px-4 py-3 font-medium text-ink-900">{{ $segment->road_name }}</td>
                                <td class="px-4 py-3 font-mono text-ink-700">{{ $segment->avg_iri }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :status="$segment->condition->value" :label="$segment->condition->label()" />
                                </td>
                                <td class="px-4 py-3 text-ink-500">{{ $segment->last_updated?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    @vite(['resources/js/road-map.js'])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initRoadMap(document.getElementById('road-map'), @json($eventPoints));
        });
    </script>
@endsection
