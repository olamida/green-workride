<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\DemandService;
use App\Services\TripMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Go Board API — Phase 1 Screen 3.
 *
 * Provides live board data for pull-to-refresh and window switching.
 */
class GoController extends Controller
{
    public function __construct(
        private TripMatchingService $matching,
        private DemandService $demand,
    ) {}

    /**
     * Get board data for a given departure window.
     */
    public function board(Request $request): JsonResponse
    {
        $window = $request->query('window', 'now');
        $presets = config('workride.board_window_presets', [
            'now' => 30,
            'later' => 240,
            'tomorrow' => 1440,
            'any' => 2880,
        ]);

        $withinMinutes = $presets[$window] ?? $presets['now'];

        $trips = $this->matching
            ->upcoming(null, $withinMinutes)
            ->map(fn ($trip) => $this->tripPayload($trip))
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'trips' => $trips,
                'demand' => $this->demand->demandSnapshot(),
                'hotspots' => $this->demand->hotspots(),
                'corridorStats' => $this->matching->corridorStats($withinMinutes),
                'corridorLive' => $this->matching->liveCorridors(),
            ],
        ]);
    }

    private function tripPayload(Trip $trip): array
    {
        $driver = $trip->driver;
        $from = ['lat' => 9.05, 'lng' => 7.45];

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
