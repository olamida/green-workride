<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\DemandService;
use App\Services\NavigationService;
use App\Services\TripMatchingService;
use Illuminate\Contracts\View\View;

/**
 * Go Board — Phase 1 Screen 3.
 *
 * The destination-first rider home:
 * - Top 55-60%: Interactive Leaflet/OSM map with user location, active rides, demand heat circles
 * - Bottom 40-45%: Sliding bottom sheet with RouteChips, window presets, LiveTripCards
 * - Large floating "Where are you going?" search pill
 * - Pull-to-refresh + live seat updates via Reverb
 */
class GoController extends Controller
{
    public function __construct(
        private TripMatchingService $matching,
    ) {}

    public function __invoke(
        NavigationService $navigation,
        DemandService $demand,
    ): View {
        $boardWindow = (int) config('workride.board_window_minutes', 2880);

        $data = [
            'corridorStats' => $this->matching->corridorStats($boardWindow),
            'corridorLive' => $this->matching->liveCorridors(),
            'trips' => $this->matching
                ->upcoming(null, 240)
                ->map(fn ($trip) => $this->tripPayload($trip))
                ->values()
                ->all(),
            'demand' => $demand->demandSnapshot(),
            'hotspots' => $demand->hotspots(),
            'mapConfig' => [
                'fct_bounds' => config('workride.fct_bounds'),
                'corridor_anchors' => config('workride.corridor_anchors'),
                'cbd' => config('workride.corridor_anchors.cbd'),
                'min_zoom' => 9,
                'default_zoom' => 12,
            ],
            'windowPresets' => config('workride.board_window_presets', [
                'now' => 30,
                'later' => 240,
                'tomorrow' => 1440,
                'any' => 2880,
            ]),
            'routeChips' => $this->routeChips(),
        ];

        return view('go.index', $data);
    }

    /**
     * Route chips for the horizontal scrolling corridor selector.
     *
     * @return array<int, array{corridor:string, label:string, long_label:string}>
     */
    private function routeChips(): array
    {
        return [
            [
                'corridor' => 'kubwa_cbd',
                'label' => 'Kubwa → Central Area',
                'long_label' => 'Kubwa Junction to Central Area — Via Berger Junction to Wuse Market',
            ],
            [
                'corridor' => 'nyanya_idu',
                'label' => 'Nyanya/Mararaba → Idu',
                'long_label' => 'Nyanya Under-Bridge to Idu Industrial — Via Mararaba & Karu',
            ],
            [
                'corridor' => 'lugbe_cbd',
                'label' => 'Lugbe/Gwagwalada → Garki',
                'long_label' => 'Lugbe Junction to Garki — Via Gwagwalada & Airport Road',
            ],
            [
                'corridor' => 'inside_town',
                'label' => 'Inside Town — Wuse, Garki, Area 1',
                'long_label' => 'Wuse Market to Area 1 — Via Garki & Federal Secretariat',
            ],
        ];
    }

    /**
     * Shape a trip for the map/list payload (same as NavigationService::tripPayload).
     */
    private function tripPayload(Trip $trip): array
    {
        $driver = $trip->driver;
        $from = ['lat' => 9.05, 'lng' => 7.45]; // Default CBD center for distance calc

        return [
            'id' => $trip->id,
            'route_name' => $trip->route_name,
            'corridor' => $trip->corridor?->value,
            'corridor_label' => $trip->corridor?->label(),
            'origin_text' => $trip->origin_text,
            'destination_text' => $trip->destination_text,
            'departure_time' => $trip->departure_time?->toIso8601String(),
            'status' => $trip->status->value,
            'available_seats' => $trip->available_seats,
            'total_seats' => $trip->total_seats,
            'fare_per_seat' => $trip->is_free_volunteer ? 0 : (float) $trip->fare_per_seat,
            'is_free_volunteer' => $trip->is_free_volunteer,
            'women_only' => $trip->women_only,
            'share_code' => $trip->share_code,
            'current_lat' => $trip->current_lat,
            'current_lng' => $trip->current_lng,
            'match_distance_m' => $trip->match_distance_m
                ?? $this->matching->distanceToTrip($trip, (float) $from['lat'], (float) $from['lng']),
            'match_score' => $trip->match_score ?? null,
            'score_reasons' => $trip->score_reasons ?? [],
            'leaving_soon' => $trip->leaving_soon ?? false,
            'driver' => $driver ? [
                'name' => $driver->name,
                'avatar' => $driver->avatar,
                'verification_level' => $driver->verification_level?->value,
                'rating_avg' => $trip->driver_rating_avg ?? null,
                'rating_count' => $trip->driver_rating_count ?? null,
            ] : null,
        ];
    }
}
