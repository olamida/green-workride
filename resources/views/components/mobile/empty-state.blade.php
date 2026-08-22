{{-- EmptyState with demand CTA — "12 people need this — Be the driver" --}}
@props([
    'icon' => 'route',
    'title' => 'No rides available',
    'description' => '',
    'demandCount' => 0,
    'topDestinations' => [],
    'ctaLabel' => 'Be the driver — Add your vehicle',
    'ctaRoute' => null,
    'ctaHref' => null,
    'onCta' => null,
    'illustration' => null, // SVG path or component
])

<?php
$hasDemand = $demandCount > 0;
$destinations = collect($topDestinations)->take(3);
?>

<div class="wr-card p-8 text-center space-y-6" data-empty-state>
    {{-- Illustration --}}
    <div class="mx-auto">
        @if ($illustration)
            {!! $illustration !!}
        @else
            <div class="mx-auto w-24 h-24 rounded-full bg-ink-100 flex items-center justify-center">
                <x-icon name="{{ $icon }}" class="h-10 w-10 text-ink-400" />
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="space-y-2">
        <h3 class="font-heading text-lg font-semibold text-ink-900">{{ $title }}</h3>
        @if ($description)
            <p class="text-ink-500">{{ $description }}</p>
        @endif

        {{-- Demand awareness --}}
        @if ($hasDemand)
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-gold-50 border border-gold-200"
                 role="status" aria-live="polite">
                <x-icon name="users" class="h-5 w-5 text-gold-600" />
                <span class="font-semibold text-gold-800">
                    {{ $demandCount }} people need a ride right now
                </span>
            </div>

            @if ($destinations->isNotEmpty())
                <div class="space-y-1 text-sm text-ink-600">
                    <p class="font-medium text-ink-700">Top destinations:</p>
                    @foreach ($destinations as $dest)
                        <div class="flex items-center gap-2 justify-center text-[13px]">
                            <span class="w-2 h-2 rounded-full bg-gold-400" aria-hidden="true"></span>
                            <span>{{ $dest['name'] ?? $dest }}</span>
                            @if (isset($dest['count']))
                                <span class="font-mono text-gold-600">{{ $dest['count'] }} people</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    {{-- CTA --}}
    @if ($ctaLabel)
        @if ($ctaHref)
            <a href="{{ $ctaHref }}"
               class="btn-primary w-full sm:w-auto py-3 text-base font-semibold"
               role="button">{{ $ctaLabel }}</a>
        @elseif ($onCta)
            <button type="button"
                    wire:click="{{ $onCta }}"
                    onclick="{{ $onCta }}"
                    class="btn-primary w-full sm:w-auto py-3 text-base font-semibold"
                    aria-label="{{ $ctaLabel }}">{{ $ctaLabel }}</button>
        @else
            <button type="button"
                    class="btn-primary w-full sm:w-auto py-3 text-base font-semibold"
                    disabled
                    aria-disabled="true">{{ $ctaLabel }}</button>
        @endif
    @endif
</div>