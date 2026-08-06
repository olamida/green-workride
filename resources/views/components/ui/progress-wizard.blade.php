@props([
    'steps' => [],
    'current' => 0,
    'showTime' => false,
])

@php $currentStep = (int) $current; @endphp

@if (empty($steps))
    {{-- nothing to render --}}
@else
    <ol role="list" aria-label="Progress" class="flex justify-between gap-2">
        @foreach ($steps as $index => $step)
            @php
                $step = is_array($step) ? $step : ['label' => $step, 'eta' => null];
                $done = $index < $currentStep;
                $isCurrent = $index === $currentStep;
            @endphp
            <li class="flex flex-1 flex-col items-center" @if ($isCurrent) aria-current="step" @endif>
                <div class="flex w-full items-center">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold transition
                        {{ $done ? 'bg-forest-600 text-white' : ($isCurrent ? 'bg-gold-100 text-gold-800 ring-4 ring-gold-200' : 'bg-ink-100 text-ink-500') }}">
                        {{ $done ? '✓' : ($index + 1) }}
                    </div>
                    @if (! $loop->last)
                        <div class="mx-2 h-0.5 flex-1 {{ $done ? 'bg-forest-500' : 'bg-ink-200' }}" aria-hidden="true"></div>
                    @endif
                </div>
                <p class="mt-1.5 text-center text-xs font-medium {{ $isCurrent ? 'text-ink-900' : ($done ? 'text-ink-500' : 'text-ink-400') }}">
                    {{ $step['label'] }}
                </p>
                @if ($showTime && ! empty($step['eta']))
                    <p class="text-[10px] text-ink-400">{{ $step['eta'] }}</p>
                @endif
            </li>
        @endforeach
    </ol>
@endif
