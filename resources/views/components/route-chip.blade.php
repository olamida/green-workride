{{-- RouteChip: corridor chip with live pulse and trip count --}}
@props([
    'corridor' => '',
    'label' => '',
    'live' => false,
    'tripCount' => 0,
    'minFare' => null,
    'href' => '#',
])

<a
    href="{{ $href }}"
    data-corridor-chip="{{ $corridor }}"
    class="inline-flex min-h-[48px] items-center gap-1.5 rounded-full border border-ink-200 dark:border-ink-700 bg-white dark:bg-ink-900 px-4 py-2 text-sm font-semibold text-ink-700 dark:text-ink-300 transition hover:border-forest-300 dark:hover:border-forest-700 hover:bg-forest-50 dark:hover:bg-forest-900/20"
>
    <span
        @class([
            'inline-block h-2 w-2 rounded-full bg-forest-500',
            'wr-pulse' => $live,
            'opacity-40' => ! $live,
        ])
        aria-hidden="true"
    ></span>
    {{ $label }}
    @if ($tripCount > 0)
        <span class="font-mono text-xs opacity-80">· {{ $tripCount }}</span>
    @endif
    @if ($live)
        <span class="sr-only"> — live trips leaving soon</span>
    @endif
    @if ($minFare !== null)
        <span class="font-mono text-xs text-forest-600">from ₦{{ number_format($minFare) }}</span>
    @endif
</a>