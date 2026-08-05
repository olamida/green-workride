@props(['label' => null, 'compact' => false, 'hotspot' => 'Berger', 'count' => '12 people'])

@if (! config('workride.animations.enabled', false))
    @php return; @endphp
@endif

@php
    // Fixed coordinate system (640×320). A light map pane pans gently behind a
    // fixed set of demand pins — the "where people wait" view. The hotspot pin
    // carries the crowd count + "almost filled" chip, with car icons clustering in.
    $mapBase = 760; // wider than viewBox so the pan loop never shows an edge.
    $pins = [
        ['x' => 128, 'y' => 208, 'tag' => 'Kubwa', 'count' => '6 people', 'hot' => false],
        ['x' => 300, 'y' => 152, 'tag' => 'Berger', 'count' => $count, 'hot' => true],
        ['x' => 470, 'y' => 118, 'tag' => 'Banex', 'count' => '9 people', 'hot' => false],
        ['x' => 560, 'y' => 220, 'tag' => 'CBD', 'count' => null, 'hot' => false],
    ];
    $cars = [
        [278, 176], [322, 186], [300, 204], [268, 192],
    ];
@endphp

<div @class([
    'wr-float relative overflow-hidden rounded-3xl border border-forest-200 bg-gradient-to-br from-forest-50 via-white to-gold-50 shadow-xl',
    $compact ? 'p-2' : 'p-3 sm:p-4',
]) aria-hidden="true">
    <svg viewBox="0 0 640 320" preserveAspectRatio="xMidYMid slice" class="h-auto w-full rounded-2xl">
        <defs>
            <pattern id="wr-dmap-grid" width="46" height="46" patternUnits="userSpaceOnUse">
                <path d="M46 0H0V46" fill="none" stroke="rgba(15,23,42,0.05)" stroke-width="1"/>
            </pattern>
        </defs>

        {{-- the panning road map (wider than the viewBox so the loop is seamless) --}}
        <g class="wr-map-pan">
            <rect width="{{ $mapBase }}" height="320" fill="#ffffff"/>
            <rect width="{{ $mapBase }}" height="320" fill="url(#wr-dmap-grid)"/>
            <path d="M-40 120 C160 110 320 150 780 120" fill="none" stroke="#e2e8f0" stroke-width="10" stroke-linecap="round"/>
            <path d="M-40 250 C180 240 420 270 780 240" fill="none" stroke="#e2e8f0" stroke-width="12" stroke-linecap="round"/>
            <path d="M180 -40 C190 100 160 200 200 360" fill="none" stroke="#e2e8f0" stroke-width="8"/>
            <path d="M430 -40 C420 120 470 220 440 360" fill="none" stroke="#e2e8f0" stroke-width="8"/>
            <path d="M-40 60 C260 50 500 90 780 60" fill="none" stroke="rgba(226,232,240,0.6)" stroke-width="3"/>
        </g>

        {{-- demand pins (static, above the panning map) --}}
        @foreach ($pins as $pin)
            <g>
                @if ($pin['hot'])
                    <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" class="wr-ring" r="6" fill="none" stroke="#fbc02d" stroke-width="2"/>
                    <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" class="wr-ring" r="6" fill="none" stroke="#fbc02d" stroke-width="2"
                            style="animation-delay: 1.2s"/>
                    <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" r="9" fill="#fbc02d" stroke="#b45309" stroke-width="1"/>
                @else
                    <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" r="7" fill="#2e7d32" stroke="#1b411f" stroke-width="1"/>
                @endif
                <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" r="3" fill="#fff"/>
                <text x="{{ $pin['x'] }}" y="{{ $pin['y'] - 16 }}" text-anchor="middle"
                      fill="#334155" font-family="Inter, sans-serif" font-size="11" font-weight="700">{{ $pin['tag'] }}</text>
            </g>
        @endforeach

        {{-- hotspot "almost filled" chip --}}
        <g transform="translate(300, 196)">
            <rect x="-54" y="-14" width="108" height="28" rx="14" fill="#0f172a"/>
            <text x="0" y="3" text-anchor="middle" fill="#fbc02d"
                  font-family="Inter, sans-serif" font-size="11" font-weight="700">{{ $count }} · almost filled</text>
        </g>

        {{-- cars clustering at the hotspot --}}
        @foreach ($cars as $i => [$cx, $cy])
            <g transform="translate({{ $cx }}, {{ $cy }})">
                <rect x="-11" y="-6" width="22" height="10" rx="3" fill="{{ $i % 2 ? '#475569' : '#2e7d32' }}"/>
                <rect x="-7" y="-10" width="14" height="5" rx="2" fill="#0f172a"/>
                <circle cx="-6" cy="5" r="2.4" fill="#0f172a"/>
                <circle cx="6" cy="5" r="2.4" fill="#0f172a"/>
            </g>
        @endforeach

        <text x="320" y="300" text-anchor="middle" fill="#475569"
              font-family="Inter, sans-serif" font-size="11" font-weight="600">Live demand — where riders are waiting now</text>
    </svg>

    @if (! $compact)
        <div class="flex flex-wrap items-center justify-between gap-2 px-2 pb-1 pt-3">
            <p class="flex items-center gap-2 text-xs font-medium text-ink-700">
                <span class="h-2 w-2 animate-pulse rounded-full bg-gold-500"></span>
                {{ $label ?? "{$hotspot} has {$count} waiting right now." }}
            </p>
            <p class="font-mono text-[11px] text-ink-400">Peak · 6:40 AM</p>
        </div>
    @endif
</div>
