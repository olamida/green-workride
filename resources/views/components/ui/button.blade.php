@props(['variant' => 'primary', 'type' => 'submit', 'href' => null])

@php
    $variants = [
        'primary' => 'bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-light)]',
        'secondary' => 'bg-white text-ink-900 ring-1 ring-inset ring-ink-200 hover:bg-ink-50',
        'ghost' => 'bg-transparent text-ink-600 hover:bg-ink-100',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'accent' => 'bg-[var(--color-accent)] text-ink-900 hover:brightness-95',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
