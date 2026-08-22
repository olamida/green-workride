{{-- RouteChip — large pill with live pulse, seats, fare, road condition --}}
@props([
    'route' => null,           // string: 'kubwa_cbd', 'nyanya_idu', 'lugbe_cbd', 'garki_wuse'
    'label' => '',             // display label override
    'vehiclesCount' => 0,      // number of vehicles leaving
    'minFare' => null,         // minimum fare in naira
    'isFree' => false,         // has free volunteer rides
    'isLive' => false,         // corridor has trips leaving within window
    'iriCondition' => 'good',  // 'excellent'|'good'|'fair'|'poor'
    'href' => null,            // link to filter board
    'active' => false,         // currently selected filter
])

<?php
$routeLabels = [
    'kubwa_cbd' => 'Kubwa to Central Area',
    'nyanya_idu' => 'Nyanya / Mararaba to Idu',
    'lugbe_cbd' => 'Lugbe / Gwagwalada to Garki',
    'garki_wuse' => 'Inside Town — Wuse, Garki, Area 1',
];
$displayLabel = $label ?: ($route ? ($routeLabels[$route] ?? $route) : 'Route');
$iriColors = [
    'excellent' => 'bg-forest-500',
    'good' => 'bg-forest-400',
    'fair' => 'bg-gold-400',
    'poor' => 'bg-red-500',
];
$iriColor = $iriColors[$iriCondition] ?? 'bg-forest-400';
$iriLabels = [
    'excellent' => 'Excellent',
    'good' => 'Good',
    'fair' => 'Fair',
    'poor' => 'Poor',
];
$iriLabel = $iriLabels[$iriCondition] ?? 'Good';
?>

@php
$classes = 'inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold transition-all duration-200 '
    . ($active
        ? 'bg-forest-600 text-white shadow-[var(--shadow-live)]'
        : 'bg-white text-ink-900 border border-ink-200 hover:border-forest-300 hover:bg-forest-50')
    . ' wr-transition-normal';
?>

<a {{ $href ? "href=\"{$href}\"" : '' }} class="{{ $classes }}" role="button" tabindex="0"
   aria-label="{{ $displayLabel }}, {{ $vehiclesCount }} vehicles leaving"
   aria-pressed="{{ $active ? 'true' : 'false' }}">
    {{-- Live pulse indicator --}}
    @if ($isLive)
        <span class="relative flex items-center" aria-live="polite" aria-atomic="true">
            <span class="w-2 h-2 rounded-full bg-forest-500 wr-pulse" aria-hidden="true"></span>
            <span class="sr-only">Live trips leaving soon</span>
        </span>
    @endif

    {{-- IRI color indicator dot --}}
    <span class="w-2.5 h-2.5 rounded-full {{ $iriColor }} flex-shrink-0" aria-hidden="true" title="Road condition: {{ $iriLabel }}"></span>

    <span class="whitespace-nowrap">{{ $displayLabel }}</span>

    {{-- Vehicle count badge --}}
    @if ($vehiclesCount > 0)
        <span class="flex items-center gap-1 bg-ink-100 px-2 py-0.5 rounded-full text-[11px] font-mono text-ink-700"
              aria-label="{{ $vehiclesCount }} vehicles">
            <x-icon name="route" class="h-3 w-3" />
            {{ $vehiclesCount }} leaving
        </span>
    @endif

    {{-- Fare or Free badge --}}
    @if ($isFree)
        <span class="flex items-center gap-1 bg-gold-100 px-2 py-0.5 rounded-full text-[11px] font-semibold text-gold-700"
              aria-label="Free volunteer rides available">
            <x-icon name="gift" class="h-3 w-3" />
            Free
        </span>
    @elseif ($minFare !== null)
        <span class="flex items-center gap-1 bg-white px-2 py-0.5 rounded-full text-[11px] font-mono text-forest-600 border border-ink-200"
              aria-label="From {{ number_format($minFare, 0) }} Naira">
            ₦{{ number_format($minFare, 0) }}
        </span>
    @endif
</a>