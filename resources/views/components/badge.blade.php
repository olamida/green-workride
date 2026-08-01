@props(['status' => 'pending'])

@php
    $styles = [
        'pending' => 'border-gold-200 bg-gold-50 text-gold-800',
        'approved' => 'border-forest-200 bg-forest-50 text-forest-700',
        'rejected' => 'border-red-200 bg-red-50 text-red-700',
    ];
    $label = ucfirst($status);
@endphp

<span class="rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $styles[$status] ?? $styles['pending'] }}">
    {{ $label }}
</span>
