@props(['label' => null, 'compact' => false, 'origin' => 'Kubwa', 'destination' => 'CBD', 'eta' => '≈ 45 min', 'distance' => '22 km'])

@if (! config('workride.animations.enabled', false))
    @php return; @endphp
@endif

@php
    // Fixed coordinate system (640×300). A Google-directions style route from the
    // passenger's pickup to the office destination — blue path revealed in gold,
    // with a car driving along it and a live ETA chip. The "booked ride" map.
    $route = 'M 108 228 C 190 208 240 190 300 150 S 430 96 520 64';
    $origin = ['x' => 108, 'y' => 228];
    $destination = ['x' => 520, 'y' => 64];
@endphp

<div @class([
    'wr-float relative overflow-hidden rounded-3xl border border-ink-800 bg-ink-950 shadow-2xl',
    $compact ? 'p-2' : 'p-3 sm:p-4',
]) aria-hidden="true">
    <svg viewBox="0 0 640 300" preserveAspectRatio="xMidYMid slice" class="h-auto w-full rounded-2xl">
        <defs>
            <pattern id="wr-nav-grid" width="42" height="42" patternUnits="userSpaceOnUse">
                <path d="M42 0H0V42" fill="none" stroke="rgba(226,232,240,0.07)" stroke-width="1"/>
            </pattern>
            <linearGradient id="wr-nav-glow" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#2e7d32" stop-opacity="0.5"/>
                <stop offset="100%" stop-color="#fbc02d" stop-opacity="0.12"/>
            </linearGradient>
        </defs>

        <rect width="640" height="300" fill="url(#wr-nav-grid)"/>
        <rect width="640" height="300" fill="url(#wr-nav-glow)"/>

        {{-- faint side roads --}}
        <path d="M-20 120 C180 110 320 180 660 120" fill="none" stroke="rgba(226,232,240,0.08)" stroke-width="3"/>
        <path d="M220 -20 C230 100 180 200 240 320" fill="none" stroke="rgba(226,232,240,0.08)" stroke-width="3"/>
        <path d="M480 -20 C470 90 520 180 500 320" fill="none" stroke="rgba(226,232,240,0.08)" stroke-width="3"/>

        {{-- the route — base lane, gold draw-in, then animated flow --}}
        <path d="{{ $route }}" fill="none" stroke="#334155" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="{{ $route }}" class="wr-route-draw" fill="none" stroke="#fbc02d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="{{ $route }}" class="wr-dash-flow" fill="none" stroke="#5fa970" stroke-width="1.5" stroke-linecap="round" opacity="0.9"/>

        {{-- the moving car --}}
        <g class="wr-car-drive" style="offset-path: path('{{ $route }}');">
            <g class="wr-car-bob">
                <rect x="-13" y="-7" width="26" height="12" rx="3.5" fill="#fbc02d"/>
                <rect x="-8" y="-12" width="16" height="6" rx="2" fill="#334155"/>
                <circle cx="-7" cy="6" r="2.8" fill="#0f172a"/>
                <circle cx="7" cy="6" r="2.8" fill="#0f172a"/>
            </g>
        </g>

        {{-- origin pin --}}
        <g>
            <circle cx="{{ $origin['x'] }}" cy="{{ $origin['y'] }}" class="wr-ring" r="6" fill="none" stroke="#2e7d32" stroke-width="2"/>
            <circle cx="{{ $origin['x'] }}" cy="{{ $origin['y'] }}" r="9" fill="#2e7d32" stroke="#1b411f" stroke-width="1"/>
            <circle cx="{{ $origin['x'] }}" cy="{{ $origin['y'] }}" r="3" fill="#fff"/>
            <text x="{{ $origin['x'] }}" y="{{ $origin['y'] + 26 }}" text-anchor="middle"
                  fill="#8fc89c" font-family="Inter, sans-serif" font-size="11" font-weight="600">Pickup</text>
        </g>

        {{-- destination pin --}}
        <g>
            <circle cx="{{ $destination['x'] }}" cy="{{ $destination['y'] }}" r="9" fill="#fbc02d" stroke="#b45309" stroke-width="1"/>
            <circle cx="{{ $destination['x'] }}" cy="{{ $destination['y'] }}" r="3" fill="#fff"/>
            <text x="{{ $destination['x'] }}" y="{{ $destination['y'] - 18 }}" text-anchor="middle"
                  fill="#fbc02d" font-family="Inter, sans-serif" font-size="11" font-weight="700">Work</text>
        </g>

        {{-- live ETA chip --}}
        <g transform="translate(560, 250)">
            <rect x="-58" y="-16" width="116" height="32" rx="12" fill="#0f172a" stroke="#334155" stroke-width="1"/>
            <text x="0" y="4" text-anchor="middle" fill="#fbc02d"
                  font-family="Inter, sans-serif" font-size="12" font-weight="700">{{ $eta }}</text>
            <text x="0" y="18" text-anchor="middle" fill="#94a3b8"
                  font-family="JetBrains Mono, monospace" font-size="9" font-weight="600">{{ $distance }}</text>
        </g>
    </svg>

    @if (! $compact)
        <div class="flex flex-wrap items-center justify-between gap-2 px-2 pb-1 pt-3">
            <p class="flex items-center gap-2 text-xs font-medium text-ink-200">
                <span class="h-2 w-2 animate-pulse rounded-full bg-forest-400"></span>
                {{ $label ?? "{$origin} → {$destination} · your car is on the way" }}
            </p>
            <p class="font-mono text-[11px] text-ink-400">{{ $eta }} · {{ $distance }}</p>
        </div>
    @endif
</div>
