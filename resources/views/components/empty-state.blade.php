{{-- EmptyState: demand-aware empty state with CTA --}}
@props([
    'title' => 'No rides right now',
    'subtitle' => 'Check back in a few minutes or be the first to offer a ride.',
    'demand' => null, // ['people' => int, 'top_destinations' => array]
    'hotspots' => [], // array of junction hotspots
    'showBeDriver' => false,
    'beDriverCorridor' => '',
    'ctaText' => 'I need a ride',
    'ctaRoute' => 'demand.index',
])

<div class="rounded-2xl border border-ink-200 bg-white px-6 py-10 text-center">
    <p class="font-heading text-lg font-semibold text-ink-900">
        @if ($demand && $demand['people'] > 0)
            {{ $demand['people'] }} people want this journey
        @else
            {{ $title }}
        @endif
    </p>
    <p class="mx-auto mt-1 max-w-md text-sm text-ink-500">
        @if ($demand && $demand['people'] > 0)
            @if (count($demand['top_destinations']))
                Demand is live for <strong>{{ implode(', ', $demand['top_destinations']) }}</strong> — a driver is being matched.
            @else
                Demand is live and a driver is being matched.
            @endif
            <a href="{{ route($ctaRoute) }}" class="font-semibold text-forest-600 hover:underline">Check in at your junction</a> to strengthen the signal.
        @else
            {{ $subtitle }}
        @endif
    </p>

    @if (count($hotspots))
        <ul class="mx-auto mt-4 max-w-md space-y-2 text-left">
            @foreach (collect($hotspots)->take(3) as $hotspot)
                <li class="flex items-center justify-between gap-3 rounded-xl border border-ink-100 bg-forest-50/40 px-4 py-2.5 text-sm">
                    <span class="min-w-0">
                        <span class="block truncate font-semibold text-ink-900">{{ $hotspot['name'] }}</span>
                        <span class="block text-xs text-ink-500">
                            {{ $hotspot['people'] }} people waiting
                            @if ($hotspot['corridor'])
                                · {{ $hotspot['corridor'] }}
                            @endif
                        </span>
                    </span>
                    @if ($showBeDriver)
                        <a href="{{ route('trips.create', ['corridor' => $hotspot['corridor'] ?? '']) }}" class="shrink-0 rounded-full bg-forest-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-forest-700">
                            Be the driver →
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
        @if ($showBeDriver)
            <a href="{{ route('trips.create') }}" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">Publish a ride</a>
        @endif
        <a href="{{ route($ctaRoute) }}" class="rounded-xl border border-forest-600 px-4 py-2 text-sm font-semibold text-forest-700 transition hover:bg-forest-50">{{ $ctaText }}</a>
    </div>
</div>