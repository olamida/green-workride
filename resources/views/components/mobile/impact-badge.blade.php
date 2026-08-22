{{-- ImpactBadge — CO2 / trees — "You have saved..." --}}
@props([
    'co2Kg' => 0,
    'trees' => 0,
    'fuelLitres' => 0,
    'level' => 1,
    'greenPoints' => 0,
    'size' => 'md',        // 'sm' | 'md' | 'lg'
    'showBreakdown' => false,
])

<?php
$co2 = number_format($co2Kg, 1);
$trees = number_format($trees, 1);
$fuel = number_format($fuelLitres, 1);
$points = number_format($greenPoints, 0);

$sizes = [
    'sm' => ['card' => 'p-3', 'icon' => 'h-8 w-8', 'value' => 'text-xl', 'label' => 'text-xs'],
    'md' => ['card' => 'p-4', 'icon' => 'h-10 w-10', 'value' => 'text-2xl', 'label' => 'text-sm'],
    'lg' => ['card' => 'p-5', 'icon' => 'h-12 w-12', 'value' => 'text-3xl', 'label' => 'text-base'],
];
$sizeConfig = $sizes[$size] ?? $sizes['md'];
?>

<div class="space-y-3">
    {{-- Main impact card --}}
    <div class="wr-card {{ $sizeConfig['card'] }} relative overflow-hidden">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 rounded-2xl bg-forest-100 {{ $sizeConfig['icon'] }} flex items-center justify-center">
                <x-icon name="leaf" class="h-6 w-6 text-forest-600" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-heading {{ $sizeConfig['value'] }} font-bold text-forest-900 tabular-nums">
                    {{ $co2 }} kg CO₂ saved
                </p>
                <p class="{{ $sizeConfig['label'] }} text-ink-500 mt-0.5">
                    That's like {{ $trees }} trees grown · {{ $fuel }}L fuel saved
                </p>
            </div>
            <div class="flex-shrink-0 text-right">
                <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gold-100 text-gold-700 text-xs font-semibold">
                    <x-icon name="star" class="h-3 w-3 fill-current" />
                    {{ $points }} pts
                </div>
                <div class="text-[10px] text-ink-400 mt-1">Level {{ $level }}</div>
            </div>
        </div>
    </div>

    {{-- Breakdown --}}
    @if ($showBreakdown)
        <div class="grid grid-cols-3 gap-2">
            <div class="wr-card p-3 text-center">
                <div class="font-heading font-mono text-xl font-bold text-forest-700">{{ $co2 }}</div>
                <div class="text-[11px] text-ink-500">kg CO₂</div>
            </div>
            <div class="wr-card p-3 text-center">
                <div class="font-heading font-mono text-xl font-bold text-forest-700">{{ $trees }}</div>
                <div class="text-[11px] text-ink-500">Trees</div>
            </div>
            <div class="wr-card p-3 text-center">
                <div class="font-heading font-mono text-xl font-bold text-forest-700">{{ $fuel }}</div>
                <div class="text-[11px] text-ink-500">Litres fuel</div>
            </div>
        </div>
    @endif
</div>