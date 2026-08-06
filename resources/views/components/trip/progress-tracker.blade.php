@props([
    'progress' => [],
    'live' => false,
    'id' => null,
])

@php
    $pointCount = count($progress);
@endphp

@if ($pointCount > 0)
    <ol role="list" aria-label="Ride progress" @if ($live) x-data="tripLive({ tripId: {{ $id ?? 0 }}, initial: {{ $live['available_seats'] ?? 0 }}, requests: 0 })" @endif class="relative">
        @foreach ($progress as $point)
            @php
                $status = $point['status'] ?? 'upcoming';
                $passed = $status === 'passed';
                $current = $status === 'current';
                $eta = $point['eta_minutes'] ?? $point['eta'] ?? null;
                $distance = $point['distance_from_origin_km'] ?? $point['distance'] ?? null;
            @endphp

            <li data-wp-id="{{ $point['id'] }}" data-wp-status="{{ $status }}"
                @if ($current) aria-current="step" @endif
                class="relative flex gap-3">
                @if (! $loop->last)
                    <span class="absolute left-[15px] top-9 -bottom-4 w-px {{ $passed ? 'bg-forest-400' : 'bg-ink-200' }}" aria-hidden="true"></span>
                @endif

                <span data-wp-dot
                      class="wr-wp-dot mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition
                             {{ $current ? 'bg-gold-100 text-gold-800 ring-2 ring-gold-300' : ($passed ? 'bg-forest-100 text-forest-700' : 'bg-ink-100 text-ink-500') }}">
                    @if ($passed)
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/>
                        </svg>
                    @elseif ($current)
                        <span class="h-2.5 w-2.5 rounded-full bg-gold-500"></span>
                    @else
                        <span class="font-mono text-xs font-semibold">{{ $point['sequence'] }}</span>
                    @endif
                </span>

                <div class="flex-1 pb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <p data-wp-label
                           class="text-sm transition {{ $current ? 'font-semibold text-gold-800' : ($passed ? 'text-ink-500 line-through decoration-ink-300' : 'text-ink-800') }}">
                            {{ $point['label'] }}
                        </p>
                        @if (! empty($point['is_major_hub']))
                            <span class="rounded-full bg-forest-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-forest-700">Hub</span>
                        @endif
                        <span data-wp-now @if (! $current) hidden @endif
                              class="inline-flex items-center gap-1 rounded-full bg-gold-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gold-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-gold-500"></span>
                            {{ $live ? 'NOW' : 'Next stop' }}
                        </span>
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-500">
                        @if ($eta !== null)
                            <span class="inline-flex items-center gap-1">
                                <x-icon name="clock" class="h-3.5 w-3.5" />
                                <span data-wp-eta>{{ (int) $eta }} min</span>
                            </span>
                        @endif
                        @if ($distance !== null)
                            <span data-wp-distance>{{ number_format((float) $distance, 1) }} km</span>
                        @endif
                        @if ($point['reached_at'])
                            <span data-wp-reached class="text-ink-400">Reached {{ \Illuminate\Support\Carbon::parse($point['reached_at'])->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
@else
    <p class="text-sm text-ink-500">No stops published for this ride yet.</p>
@endif
