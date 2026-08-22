{{-- StatusBadge — Leaving Soon, Soft Hold, Verified L3, Free --}}
@props([
    'status' => '',           // 'leaving-soon' | 'soft-hold' | 'verified' | 'free' | 'full' | 'cancelled' | 'overdue' | 'live'
    'label' => null,          // override label
    'countdown' => null,      // seconds remaining for soft-hold
    'size' => 'md',           // 'sm' | 'md' | 'lg'
    'pulse' => false,         // animate for live/leaving-soon
])

<?php
$statusConfig = [
    'leaving-soon' => ['bg' => 'bg-gold-100', 'text' => 'text-gold-700', 'icon' => 'clock', 'default' => 'Leaving soon'],
    'soft-hold' => ['bg' => 'bg-gold-100', 'text' => 'text-gold-700', 'icon' => 'timer', 'default' => 'Seat held'],
    'verified' => ['bg' => 'bg-forest-100', 'text' => 'text-forest-700', 'icon' => 'shield-check', 'default' => 'Verified'],
    'free' => ['bg' => 'bg-gold-100', 'text' => 'text-gold-700', 'icon' => 'gift', 'default' => 'Free ride'],
    'full' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'users-round', 'default' => 'Full'],
    'cancelled' => ['bg' => 'bg-ink-100', 'text' => 'text-ink-600', 'icon' => 'x-circle', 'default' => 'Cancelled'],
    'overdue' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'alert-circle', 'default' => 'Overdue'],
    'live' => ['bg' => 'bg-forest-100', 'text' => 'text-forest-700', 'icon' => 'radio', 'default' => 'Live now'],
    'boarded' => ['bg' => 'bg-forest-100', 'text' => 'text-forest-700', 'icon' => 'check-circle', 'default' => 'Boarded'],
    'completed' => ['bg' => 'bg-ink-100', 'text' => 'text-ink-600', 'icon' => 'check-circle', 'default' => 'Completed'],
];

$config = $statusConfig[$status] ?? ['bg' => 'bg-ink-100', 'text' => 'text-ink-600', 'icon' => 'help-circle', 'default' => ucfirst($status)];
$displayLabel = $label ?? $config['default'];

$sizes = [
    'sm' => 'px-2 py-0.5 text-[10px] gap-1',
    'md' => 'px-2.5 py-1 text-xs gap-1.5',
    'lg' => 'px-3 py-1.5 text-sm gap-2',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
?>

@if ($status === 'soft-hold' && $countdown !== null)
    <x-status-badge-countdown :status="$status" :label="$displayLabel" :countdown="$countdown" :size="$size" :pulse="$pulse" :config="$config" :sizeClass="$sizeClass" />
@else
    <span class="inline-flex items-center {{ $sizeClass }} rounded-full font-semibold {{ $config['bg'] }} {{ $config['text'] }} {{ $pulse ? 'wr-pulse' : '' }} wr-transition-normal"
          role="status" aria-live="polite" aria-atomic="true">
        <x-icon name="{{ $config['icon'] }}" class="h-3.5 w-3.5 flex-shrink-0" aria-hidden="true" />
        <span class="whitespace-nowrap">{{ $displayLabel }}</span>
    </span>
@endif