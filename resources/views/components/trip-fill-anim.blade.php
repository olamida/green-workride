@props(['label' => null, 'compact' => false, 'departing' => 'Kubwa → CBD', 'seats' => '3 cars boarding'])

@if (! config('workride.animations.enabled', false))
    @php return; @endphp
@endif

@php
    // Fixed coordinate system (640×320). A corridor stop on the left fills cars
    // seat-by-seat; the top car then drives off along the route — the trip board's
    // "cars filling, cars departing" brand mark.
    $stop = ['x' => 92, 'y' => 176];
    $route = 'M 150 176 C 260 176 300 120 430 120 S 600 150 620 96';
    // Queued cars below the stop; each seat fills in with a staggered delay.
    $cars = [
        ['x' => 150, 'y' => 244, 'delay' => 0],
        ['x' => 236, 'y' => 244, 'delay' => 1.1],
        ['x' => 322, 'y' => 244, 'delay' => 2.2],
    ];
    // Seats per car (4 each) — fill left-to-right inside each car.
    $seatOffsets = [-15, -5, 5, 15];
@endphp

<div @class([
    'wr-float relative overflow-hidden rounded-3xl border border-ink-800 bg-ink-950 shadow-2xl',
    $compact ? 'p-2' : 'p-3 sm:p-4',
]) aria-hidden="true">
    <svg viewBox="0 0 640 320" preserveAspectRatio="xMidYMid slice" class="h-auto w-full rounded-2xl">
        <defs>
            <pattern id="wr-tfill-grid" width="42" height="42" patternUnits="userSpaceOnUse">
                <path d="M42 0H0V42" fill="none" stroke="rgba(226,232,240,0.07)" stroke-width="1"/>
            </pattern>
            <linearGradient id="wr-tfill-glow" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#2e7d32" stop-opacity="0.5"/>
                <stop offset="100%" stop-color="#fbc02d" stop-opacity="0.12"/>
            </linearGradient>
        </defs>

        <rect width="640" height="320" fill="url(#wr-tfill-grid)"/>
        <rect width="640" height="320" fill="url(#wr-tfill-glow)"/>

        {{-- faint map arteries --}}
        <path d="M-20 60 C140 70 300 40 660 56" fill="none" stroke="rgba(226,232,240,0.08)" stroke-width="2"/>
        <path d="M90 -20 C100 90 200 160 300 330" fill="none" stroke="rgba(226,232,240,0.08)" stroke-width="2"/>

        {{-- the route the departing car travels --}}
        <path d="{{ $route }}" fill="none" stroke="#334155" stroke-width="3" stroke-linecap="round"/>
        <path d="{{ $route }}" class="wr-dash-flow" fill="none" stroke="#5fa970" stroke-width="2" stroke-linecap="round"/>

        {{-- the corridor stop --}}
        <g>
            <circle cx="{{ $stop['x'] }}" cy="{{ $stop['y'] }}" class="wr-scan" r="6"/>
            <circle cx="{{ $stop['x'] }}" cy="{{ $stop['y'] }}" r="9" fill="#fbc02d"/>
            <circle cx="{{ $stop['x'] }}" cy="{{ $stop['y'] }}" r="4" fill="#0f172a"/>
            <text x="{{ $stop['x'] }}" y="{{ $stop['y'] - 16 }}" text-anchor="middle"
                  fill="#fbc02d" font-family="Inter, sans-serif" font-size="12" font-weight="700">{{ $departing }}</text>
        </g>

        {{-- departing car — drives the route then fades off --}}
        <g class="wr-car-drive" style="offset-path: path('{{ $route }}');">
            <g class="wr-car-bob">
                <rect x="-24" y="-12" width="48" height="20" rx="6" fill="#fbc02d"/>
                <rect x="-14" y="-19" width="28" height="9" rx="3" fill="#334155"/>
                <circle cx="-14" cy="9" r="4" fill="#0f172a"/>
                <circle cx="14" cy="9" r="4" fill="#0f172a"/>
            </g>
        </g>

        {{-- queued cars filling up --}}
        @foreach ($cars as $i => $car)
            <g transform="translate({{ $car['x'] }}, {{ $car['y'] }})">
                <rect x="-36" y="-12" width="72" height="20" rx="6" fill="#1e293b" stroke="#334155" stroke-width="1"/>
                <rect x="-26" y="-19" width="52" height="9" rx="3" fill="#0f172a"/>
                <circle cx="-24" cy="9" r="4" fill="#0f172a"/>
                <circle cx="24" cy="9" r="4" fill="#0f172a"/>
                @foreach ($seatOffsets as $s => $seat)
                    <circle cx="{{ $seat }}" cy="-10" r="2.4" fill="#8fc89c" class="wr-seat-fill"
                            style="animation-delay: {{ $car['delay'] + $s * 0.35 }}s"/>
                @endforeach
            </g>
        @endforeach

        {{-- boarding caption over the queue --}}
        <text x="310" y="296" text-anchor="middle" fill="#94a3b8"
              font-family="Inter, sans-serif" font-size="11" font-weight="600">{{ $seats }}</text>
    </svg>

    @if (! $compact)
        <div class="flex flex-wrap items-center justify-between gap-2 px-2 pb-1 pt-3">
            <p class="flex items-center gap-2 text-xs font-medium text-ink-200">
                <span class="h-2 w-2 animate-pulse rounded-full bg-forest-400"></span>
                {{ $label ?? 'Seats filling — board before it departs.' }}
            </p>
            <p class="font-mono text-[11px] text-ink-400">{{ $departing }}</p>
        </div>
    @endif
</div>
