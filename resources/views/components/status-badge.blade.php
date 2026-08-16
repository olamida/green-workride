{{-- StatusBadge: pill badge for ride status (Live, Leaving soon, Book ahead, Full, etc.) --}}
@props([
    'status' => '', // live | leaving_soon | book_ahead | full | cancelled | free | women_only
    'label' => null,
    'dot' => true,
])

@php
    $config = [
        'live' => ['bg' => 'bg-forest-50', 'text' => 'text-forest-700', 'dot' => 'bg-forest-500', 'label' => 'Live now'],
        'leaving_soon' => ['bg' => 'bg-gold-100', 'text' => 'text-gold-800', 'dot' => 'bg-gold-500', 'label' => 'Leaving soon'],
        'book_ahead' => ['bg' => 'bg-ink-100', 'text' => 'text-ink-600', 'dot' => 'bg-ink-400', 'label' => 'Book ahead'],
        'full' => ['bg' => 'bg-danger/10', 'text' => 'text-danger', 'dot' => 'bg-danger', 'label' => 'Full'],
        'cancelled' => ['bg' => 'bg-ink-100', 'text' => 'text-ink-500', 'dot' => 'bg-ink-400', 'label' => 'Cancelled'],
        'free' => ['bg' => 'bg-gold-100', 'text' => 'text-gold-800', 'dot' => 'bg-gold-500', 'label' => 'Free'],
        'women_only' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'dot' => 'bg-rose-500', 'label' => 'Women-only'],
    ];

    $c = $config[$status] ?? $config['live'];
    $displayLabel = $label ?? $c['label'];
@endphp

@if ($dot)
    <span class="inline-flex items-center gap-1.5 rounded-full {{ $c['bg'] }} px-2.5 py-0.5 text-xs font-semibold {{ $c['text'] }}">
        <span class="h-1.5 w-1.5 rounded-full {{ $c['dot'] }}"></span>
        {{ $displayLabel }}
    </span>
@else
    <span class="inline-flex items-center gap-1.5 rounded-full {{ $c['bg'] }} px-2.5 py-0.5 text-xs font-semibold {{ $c['text'] }}">
        {{ $displayLabel }}
    </span>
@endif