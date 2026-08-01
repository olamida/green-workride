@props(['type' => 'success'])

<div @class([
    'mb-6 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm',
    'border-forest-200 bg-forest-50 text-forest-800' => $type === 'success',
    'border-red-200 bg-red-50 text-red-800' => $type === 'error',
    'border-gold-200 bg-gold-50 text-gold-900' => $type === 'warning',
])>
    <span class="mt-0.5 text-base leading-none">
        {{ $type === 'success' ? '✓' : ($type === 'error' ? '!' : '●') }}
    </span>
    <p>{{ $slot }}</p>
</div>
