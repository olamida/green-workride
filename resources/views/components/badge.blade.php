@props(['status' => 'pending', 'label' => null])

@php
    $styles = [
        'pending' => 'border-gold-200 bg-gold-50 text-gold-800',
        'pending_manual_review' => 'border-gold-300 bg-gold-100 text-gold-900',
        'approved' => 'border-forest-200 bg-forest-50 text-forest-700',
        'rejected' => 'border-red-200 bg-red-50 text-red-700',
        'excellent' => 'border-forest-200 bg-forest-50 text-forest-700',
        'good' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'fair' => 'border-gold-200 bg-gold-50 text-gold-800',
        'poor' => 'border-red-200 bg-red-50 text-red-700',
    ];
    $labels = [
        'pending_manual_review' => 'Needs review',
    ];
    $label = $label ?? ($labels[$status] ?? ucfirst($status));
@endphp

<span class="rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $styles[$status] ?? $styles['pending'] }}">
    {{ $label }}
</span>
