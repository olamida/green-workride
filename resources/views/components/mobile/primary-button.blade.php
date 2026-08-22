{{-- PrimaryButton — full-width or large, 56px min height, haptic on press --}}
@props([
    'variant' => 'primary',   // 'primary' | 'secondary' | 'ghost' | 'danger' | 'accent'
    'size' => 'lg',           // 'sm' | 'md' | 'lg' | 'xl'
    'fullWidth' => false,
    'disabled' => false,
    'loading' => false,
    'type' => 'button',
    'icon' => null,           // icon name from icon.blade.php
    'iconPosition' => 'left', // 'left' | 'right'
])

<?php
$variants = [
    'primary' => 'bg-forest-600 text-white hover:bg-forest-700 active:bg-forest-800 focus:ring-forest-500',
    'secondary' => 'bg-white text-forest-600 border border-forest-300 hover:bg-forest-50 active:bg-forest-100 focus:ring-forest-500',
    'ghost' => 'bg-transparent text-forest-600 hover:bg-forest-50 active:bg-forest-100 focus:ring-forest-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus:ring-red-500',
    'accent' => 'bg-gold-400 text-ink-900 hover:bg-gold-500 active:bg-gold-600 focus:ring-gold-400',
];

$sizes = [
    'sm' => 'px-3 py-2 text-sm min-h-[40px]',
    'md' => 'px-4 py-2.5 text-base min-h-[48px]',
    'lg' => 'px-6 py-3 text-base font-semibold min-h-[56px]',
    'xl' => 'px-8 py-4 text-lg font-semibold min-h-[64px]',
];

$variantClass = $variants[$variant] ?? $variants['primary'];
$sizeClass = $sizes[$size] ?? $sizes['lg'];
$widthClass = $fullWidth ? 'w-full' : 'inline-flex';
$disabledClass = $disabled || $loading ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
?>

<button type="{{ $type }}"
        class="flex items-center justify-center gap-2 rounded-xl font-mono font-semibold transition-all duration-150
               {{ $variantClass }} {{ $sizeClass }} {{ $widthClass }} {{ $disabledClass }} wr-transition-fast"
        :disabled="{{ $disabled || $loading ? 'true' : 'false' }}"
        @click="haptic('{{ $variant === 'danger' ? 'warning' : ($variant === 'primary' ? 'medium' : 'light') }}')"
        aria-busy="{{ $loading ? 'true' : 'false' }}"
        aria-disabled="{{ $disabled ? 'true' : 'false' }}">
    @if ($loading)
        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"></circle>
            <circle class="opacity-75" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31 31" stroke-dashoffset="31" stroke-linecap="round"></circle>
        </svg>
        <span>{{ $slot ?? 'Loading...' }}</span>
    @else
        @if ($icon && $iconPosition === 'left')
            <x-icon name="{{ $icon }}" class="h-5 w-5" aria-hidden="true" />
        @endif
        <span>{{ $slot }}</span>
        @if ($icon && $iconPosition === 'right')
            <x-icon name="{{ $icon }}" class="h-5 w-5" aria-hidden="true" />
        @endif
    @endif
</button>

<script>
document.addEventListener('alpine:init', () => {
    // Global haptic feedback utility
    if (!window.wrHaptic) {
        window.wrHaptic = (type = 'light') => {
            if (!navigator.vibrate) return;
            const patterns = {
                light: [10],
                medium: [20],
                heavy: [30],
                success: [10, 50, 10],
                warning: [30, 30, 30],
                error: [50, 50, 50],
            };
            navigator.vibrate(patterns[type] || patterns.light);
        };
    }
});
</script>