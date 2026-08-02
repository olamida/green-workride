@props(['active' => false, 'href' => '#', 'icon' => null])

<a href="{{ $href }}" @class([
    'flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium transition',
    'bg-forest-600 text-white shadow-sm' => $active,
    'text-ink-600 hover:bg-ink-100 hover:text-ink-900' => ! $active,
])>
    @if ($icon)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
    {{ $slot }}
</a>
