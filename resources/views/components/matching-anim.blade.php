@props(['label' => null, 'compact' => false, 'online' => '1,240 verified civil servants online'])

@if (! config('workride.animations.enabled', false))
    {{-- Animated SVG brand cards are decorative. They are disabled site-wide
         until the animation language is reviewed (config/workride.php). --}}
    @php return; @endphp
@endif

@php
    // Fixed coordinate system (640×320). The passenger sits centre-right and a
    // search wave radiates outward while the route-finder probes candidate
    // paths between rider nodes — the app's "searching algorithm" brand mark.
    $passenger = ['x' => 470, 'y' => 66];
    $nodes = [
        ['x' => 148, 'y' => 58, 'tag' => 'Kubwa'],
        ['x' => 232, 'y' => 198, 'tag' => 'Berger'],
        ['x' => 62, 'y' => 248, 'tag' => null],
        ['x' => 330, 'y' => 118, 'tag' => 'Banex'],
        ['x' => 560, 'y' => 224, 'tag' => 'CBD'],
        ['x' => 416, 'y' => 282, 'tag' => null],
        ['x' => 600, 'y' => 40, 'tag' => null],
        ['x' => 240, 'y' => 90, 'tag' => null],
    ];
    // Candidate routes probed by the matcher (dashed, flowing).
    $probes = [
        [$passenger, [560, 224]],
        [$passenger, [330, 118]],
        [['x' => 330, 'y' => 118], [232, 198]],
        [['x' => 330, 'y' => 118], [148, 58]],
        [['x' => 232, 'y' => 198], [62, 248]],
        [['x' => 232, 'y' => 198], [416, 282]],
        [['x' => 148, 'y' => 58], [240, 90]],
        [['x' => 560, 'y' => 224], [416, 282]],
    ];
    // The winning path that gets revealed in gold.
    $winner = 'M 148 58 L 240 90 L 330 118 L 470 66';
@endphp

<div @class([
    'wr-float relative overflow-hidden rounded-3xl border border-ink-800 bg-ink-950 shadow-2xl',
    $compact ? 'p-2' : 'p-3 sm:p-4',
]) aria-hidden="true">
    <svg viewBox="0 0 640 320" preserveAspectRatio="xMidYMid slice" class="h-auto w-full rounded-2xl">
        <defs>
            <pattern id="wr-grid" width="42" height="42" patternUnits="userSpaceOnUse">
                <path d="M42 0H0V42" fill="none" stroke="rgba(226,232,240,0.07)" stroke-width="1"/>
            </pattern>
            <linearGradient id="wr-glow" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#2e7d32" stop-opacity="0.5"/>
                <stop offset="100%" stop-color="#fbc02d" stop-opacity="0.12"/>
            </linearGradient>
        </defs>

        <rect width="640" height="320" fill="url(#wr-grid)"/>
        <rect width="640" height="320" fill="url(#wr-glow)"/>

        {{-- faint map arteries --}}
        <path d="M-20 260 C120 260 170 140 330 150 S540 92 660 100" fill="none" stroke="rgba(226,232,240,0.08)" stroke-width="2"/>
        <path d="M90 -20 C100 90 200 160 300 310" fill="none" stroke="rgba(226,232,240,0.08)" stroke-width="2"/>

        {{-- candidate probes --}}
        @foreach ($probes as [$a, $b])
            <line x1="{{ $a['x'] }}" y1="{{ $a['y'] }}" x2="{{ $b[0] }}" y2="{{ $b[1] }}"
                  class="wr-dash-flow" stroke="#5fa970" stroke-width="1.5" stroke-linecap="round"/>
        @endforeach

        {{-- winning route revealed in gold --}}
        <path d="{{ $winner }}" class="wr-dash-reveal" fill="none" stroke="#fbc02d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

        {{-- radar scan radiating from the passenger --}}
        <circle cx="{{ $passenger['x'] }}" cy="{{ $passenger['y'] }}" class="wr-scan" r="8"/>
        <circle cx="{{ $passenger['x'] }}" cy="{{ $passenger['y'] }}" class="wr-scan" r="8" style="animation-delay: 1.2s"/>

        {{-- rider nodes --}}
        @foreach ($nodes as $i => $node)
            @php
                $pulseClass = $i % 2 === 0 ? ' wr-pulse' : '';
                $delayStyle = $i % 3 === 0 ? 'animation-delay: 0.6s' : '';
            @endphp
            <g class="{{ trim('wr-node' . $pulseClass) }}" style="{{ $delayStyle }}">
                <circle cx="{{ $node['x'] }}" cy="{{ $node['y'] }}" r="5" fill="#0f172a" stroke="#2e7d32" stroke-width="2"/>
                <circle cx="{{ $node['x'] }}" cy="{{ $node['y'] }}" r="2" fill="#8fc89c"/>
                @if ($node['tag'])
                    <text x="{{ $node['x'] }}" y="{{ $node['y'] - 12 }}" text-anchor="middle"
                          fill="#94a3b8" font-family="Inter, sans-serif" font-size="11" font-weight="600">{{ $node['tag'] }}</text>
                @endif
            </g>
        @endforeach

        {{-- the passenger --}}
        <g>
            <circle cx="{{ $passenger['x'] }}" cy="{{ $passenger['y'] }}" r="10" fill="#fbc02d"/>
            <circle cx="{{ $passenger['x'] }}" cy="{{ $passenger['y'] }}" r="4" fill="#0f172a"/>
            <text x="{{ $passenger['x'] }}" y="{{ $passenger['y'] - 18 }}" text-anchor="middle"
                  fill="#fbc02d" font-family="Inter, sans-serif" font-size="12" font-weight="700">YOU</text>
        </g>
    </svg>

    @if (! $compact)
        <div class="flex flex-wrap items-center justify-between gap-2 px-2 pb-1 pt-3">
            <p class="flex items-center gap-2 text-xs font-medium text-ink-200">
                <span class="h-2 w-2 animate-pulse rounded-full bg-forest-400"></span>
                {{ $label ?? 'Searching verified riders on your corridor…' }}
            </p>
            <p class="font-mono text-[11px] text-ink-400">{{ $online }}</p>
        </div>
    @endif
</div>
