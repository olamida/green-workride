{{-- PrimaryButton: 56px min height, haptic press feedback, spinner state --}}
@props([
    'variant' => 'primary', // primary | secondary | ghost | danger | accent
    'size' => 'md', // sm | md | lg
    'disabled' => false,
    'loading' => false,
    'type' => 'button',
    'href' => null,
    'class' => '',
])

@php
    $variants = [
        'primary' => 'bg-forest-600 text-white hover:bg-forest-700 focus:ring-forest-500/30',
        'secondary' => 'bg-ink-100 text-ink-900 hover:bg-ink-200 focus:ring-ink-400/30',
        'ghost' => 'bg-transparent text-forest-600 hover:bg-forest-50 focus:ring-forest-500/30',
        'danger' => 'bg-danger text-white hover:bg-danger/90 focus:ring-danger/30',
        'accent' => 'bg-gold-400 text-ink-900 hover:bg-gold-500 focus:ring-gold-400/30',
    ];

    $sizes = [
        'sm' => 'px-4 py-2.5 text-sm',
        'md' => 'px-6 py-3.5 text-base', // 56px min height
        'lg' => 'px-8 py-4 text-lg',
    ];

    $base = 'inline-flex items-center justify-center gap-2 min-h-[56px] rounded-xl font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]';
    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . $class;
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        class="{{ $classes }}"
        @class(['pointer-events-none' => $disabled || $loading])
        @if ($disabled || $loading) aria-disabled="true" @endif
        @if ($loading) aria-busy="true" @endif
    >
        @if ($loading)
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
        @else
            {{ $slot }}
        @endif
    </a>
@else
    <button
        type="{{ $type }}"
        class="{{ $classes }}"
        :disabled="{{ $disabled || $loading ? 'true' : 'false' }}"
        @if ($loading) aria-busy="true" @endif
    >
        @if ($loading)
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
        @else
            {{ $slot }}
        @endif
    </button>
@endif