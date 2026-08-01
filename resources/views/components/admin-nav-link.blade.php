@props(['active' => false, 'href' => '#'])

<a href="{{ $href }}" @class([
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-forest-600 text-white' => $active,
    'text-ink-300 hover:bg-ink-800 hover:text-white' => ! $active,
])>{{ $slot }}</a>
