{{-- ImpactBadge: CO2/Fuel/Tree savings badge with tree equivalent --}}
@props([
    'co2Kg' => 0,
    'fuelLitres' => 0,
    'trees' => 0,
    'size' => 'md', // sm | md | lg
    'showLabel' => true,
])

@php
    $sizes = [
        'sm' => 'px-2 py-1 text-xs gap-1',
        'md' => 'px-3 py-1.5 text-sm gap-1.5',
        'lg' => 'px-4 py-2 text-base gap-2',
    ];
    $classes = 'inline-flex items-center rounded-full bg-forest-50 text-forest-700 font-semibold ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<div class="{{ $classes }}">
    @if ($trees > 0)
        <span class="flex h-5 w-5 items-center justify-center" aria-hidden="true">
            <svg class="h-full w-full text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </span>
        <span>{{ $trees }} <span class="opacity-70">trees</span></span>
    @endif

    @if ($co2Kg > 0)
        <span class="flex h-5 w-5 items-center justify-center" aria-hidden="true">
            <svg class="h-full w-full text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <span>{{ number_format($co2Kg, 1) }} <span class="opacity-70">kg CO₂</span></span>
    @endif

    @if ($fuelLitres > 0)
        <span class="flex h-5 w-5 items-center justify-center" aria-hidden="true">
            <svg class="h-full w-full text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </span>
        <span>{{ number_format($fuelLitres, 1) }} <span class="opacity-70">L fuel</span></span>
    @endif

    @if ($showLabel && ($co2Kg > 0 || $fuelLitres > 0 || $trees > 0))
        <span class="sr-only">Impact: {{ $trees ? $trees.' trees, ' : '' }}{{ $co2Kg ? number_format($co2Kg, 1).' kg CO₂, ' : '' }}{{ $fuelLitres ? number_format($fuelLitres, 1).' L fuel' : '' }}</span>
    @endif
</div>