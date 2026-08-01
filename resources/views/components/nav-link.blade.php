@props(['active' => false, 'href' => '#'])

<a href="{{ $href }}" @class([
    'rounded-lg px-3 py-1.5 font-medium transition',
    'bg-forest-600 text-white' => $active,
    'text-ink-600 hover:bg-ink-100 hover:text-ink-900' => ! $active,
])>{{ $slot }}</a>
