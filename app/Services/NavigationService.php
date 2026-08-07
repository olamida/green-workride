<?php

namespace App\Services;

use App\Models\Junction;
use App\Models\Trip;
use App\Models\Workplace;

/**
 * Navigation-first orchestration (guide §9B + navigation sprint).
 *
 * The first screen a rider sees is "Where are you going?" — a search box that
 * resolves local junctions/workplaces (with real demand volume) and falls back
 * to free OSM geocoding. Selecting a destination asks this service for the
 * route geometry plus the trips that are actually going that way, and the
 * live map asks it for everything moving nearby.
 *
 * Money, verification and booking gates are untouched — this service only
 * answers "what's out there", never mutates a wallet or a booking.
 */
class NavigationService
{
    public function __construct(
        private TripMatchingService $matching,
        private GeofenceService $geofence,
        private RoutingService $routing,
        private DemandService $demand,
    ) {}

    /**
     * Search the junction + workplace catalog, falling back to free OSM
     * geocoding when the local catalog runs dry (< 3 hits). Junction rows
     * carry the surveyed passenger volume so the rider sees "1,500+ daily".
     *
     * @return array<int, array{id:?int, name:string, lat:float, lng:float, type:string, corridor:?string, passenger_volume_daily:?int}>
     */
    public function search(string $q, ?float $lat = null, ?float $lng = null): array
    {
        $term = '%'.$q.'%';

        $junctions = Junction::with('surveys')
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('zone', 'like', $term);
            })
            ->get();

        $workplaces = Workplace::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('zone', 'like', $term)
                    ->orWhere('acronym', 'like', $term);
            })
            ->get();

        $results = collect();

        foreach ($junctions as $junction) {
            $results->push([
                'id' => $junction->id,
                'name' => $junction->name,
                'lat' => (float) $junction->lat,
                'lng' => (float) $junction->lng,
                'type' => 'junction',
                'corridor' => $junction->corridor,
                'passenger_volume_daily' => $junction->totalCounted(),
            ]);
        }

        foreach ($workplaces as $workplace) {
            $results->push([
                'id' => $workplace->id,
                'name' => trim(($workplace->acronym ? $workplace->acronym.' — ' : '').$workplace->name),
                'lat' => (float) $workplace->lat,
                'lng' => (float) $workplace->lng,
                'type' => 'workplace',
                'corridor' => $workplace->zone,
                'passenger_volume_daily' => null,
            ]);
        }

        if ($results->count() < 3) {
            $near = $lat !== null && $lng !== null ? ['lat' => $lat, 'lng' => $lng] : null;

            foreach ($this->routing->geocode($q, $near) as $geocoded) {
                $results->push($geocoded);
            }
        }

        return $results
            ->sortBy(fn (array $result) => match ($result['type']) {
                'junction' => 0,
                'workplace' => 1,
                default => 2,
            })
            ->values()
            ->all();
    }

    /**
     * Route geometry + the trips actually going that way + live demand.
     *
     * Trips come from the existing Haversine 2 km pickup match, then re-ordered
     * so rides whose corridor terminates near the destination ("going your
     * way") surface first, then soonest departure. Never hard-excludes a
     * nearby ride just because its corridor label differs — proximity wins.
     *
     * @param  array{lat:float,lng:float}  $from
     * @param  array{lat:float,lng:float}  $to
     * @return array{route:array, trips:array, demand:array}
     */
    public function directions(array $from, array $to, ?int $withinMinutes = null): array
    {
        $route = $this->routing->route($from, $to, 'driving');

        $trips = $this->matching
            ->findMatches((float) $from['lat'], (float) $from['lng'], null, $withinMinutes)
            ->map(function (Trip $trip) use ($to) {
                $trip->toward_destination = $this->towardDestination($trip, $to);

                return $trip;
            })
            ->sortBy(fn (Trip $trip) => [
                $trip->toward_destination ? 0 : 1,
                $trip->departure_time?->timestamp ?? PHP_INT_MAX,
            ])
            ->values()
            ->take(10);

        return [
            'route' => [
                'geometry' => $route['points'],
                'distance_km' => round($route['distance_m'] / 1000, 2),
                'duration_min' => round($route['duration_s'] / 60, 1),
                'provider' => $route['provider'],
            ],
            'trips' => $trips->map(fn (Trip $trip) => $this->tripPayload($trip, $from))->all(),
            'demand' => $this->demand->demandSnapshot(),
        ];
    }

    /**
     * Live/scheduled trips within a radius of a point, for the map canvas.
     *
     * @return array<int, array>
     */
    public function nearby(float $lat, float $lng, float $radiusKm = 2.0): array
    {
        $radiusM = (int) round($radiusKm * 1000);

        return $this->matching
            ->upcoming(null, 240)
            ->map(function (Trip $trip) use ($lat, $lng) {
                $trip->match_distance_m = $this->matching->distanceToTrip($trip, $lat, $lng);

                return $trip;
            })
            ->filter(fn (Trip $trip) => $trip->match_distance_m <= $radiusM)
            ->sortBy(fn (Trip $trip) => $trip->departure_time?->timestamp ?? PHP_INT_MAX)
            ->values()
            ->take(25)
            ->map(fn (Trip $trip) => $this->tripPayload($trip, ['lat' => $lat, 'lng' => $lng]))
            ->all();
    }

    /**
     * Everything the navigation-first home needs in one call: per-corridor
     * supply, which corridors are live right now, the next 4h of trips for the
     * map canvas (no location filter yet — the rider picks a destination), and
     * the live demand snapshot for the empty state.
     *
     * @return array<string, mixed>
     */
    public function homeData(): array
    {
        return [
            'corridorStats' => $this->matching->corridorStats((int) config('workride.board_window_minutes', 2880)),
            'corridorLive' => $this->matching->liveCorridors(),
            'trips' => $this->matching
                ->upcoming(null, 240)
                ->map(fn (Trip $trip) => $this->tripPayload($trip, ['lat' => 9.05, 'lng' => 7.45]))
                ->values()
                ->all(),
            'demand' => $this->demand->demandSnapshot(),
            'hotspots' => $this->demand->hotspots(),
        ];
    }

    /**
     * Does this trip's corridor terminate near the requested destination?
     * Corridor "kubwa_cbd" means Kubwa → CBD, so the terminal anchor is the
     * CBD. A ride's corridor label never hard-excludes it — only re-orders.
     *
     * @param  array{lat:float,lng:float}  $to
     */
    private function towardDestination(Trip $trip, array $to): bool
    {
        $destinationKey = match ($trip->corridor?->value) {
            'kubwa_cbd', 'lugbe_cbd' => 'cbd',
            'nyanya_idu' => 'idu',
            default => null,
        };

        $anchor = $destinationKey ? (config('workride.corridor_anchors')[$destinationKey] ?? null) : null;

        if (! $anchor) {
            return true;
        }

        $radiusM = (int) config('workride.destination_match_radius_m', 8000);

        return $this->geofence->haversine(
            (float) $to['lat'],
            (float) $to['lng'],
            (float) $anchor['lat'],
            (float) $anchor['lng'],
        ) <= $radiusM;
    }

    /**
     * Shape a trip for the map/list/directions responses. No nested models,
     * no money mutations — pure read payload.
     *
     * @param  array{lat:float,lng:float}  $from
     * @return array<string, mixed>
     */
    private function tripPayload(Trip $trip, array $from): array
    {
        $driver = $trip->driver;

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
            'toward_destination' => $trip->toward_destination ?? false,
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
