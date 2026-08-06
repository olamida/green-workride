<?php

namespace App\Services;

use App\Models\Trip;
use Throwable;

/**
 * Passenger-to-vehicle connect guide (guide §5 / guide-v4.0 last-mile).
 *
 * WorkRide's hard job is the last 200-500 m at informal junctions — not
 * city-wide turn-by-turn. This service resolves WHERE the passenger should
 * walk to (the live vehicle when it is reporting, otherwise the next boarding
 * waypoint) and produces the walking distance/ETA the guide shows, with a
 * zero-cost straight-line fallback when the routing provider is unreachable.
 */
class ConnectGuideService
{
    public function __construct(
        private GeofenceService $geofence,
        private RoutingService $routing,
    ) {}

    /**
     * Where the passenger should go, resolved in trust order:
     *
     *   1. Live vehicle coordinates (only meaningful while the trip is active
     *      or the driver published with a position).
     *   2. The next boarding waypoint (scheduled trips with a stop list).
     *   3. "none" — no pin yet; the guide shows the departure countdown and
     *      encourages the driver to share a position.
     *
     * @return array{type:string,lat:?float,lng:?float,label:string}
     */
    public function targetFor(Trip $trip): array
    {
        $lat = $trip->current_lat;
        $lng = $trip->current_lng;

        if ($lat !== null && $lng !== null && (float) $lat !== 0.0 && (float) $lng !== 0.0) {
            return [
                'type' => 'live',
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'label' => $this->vehicleLabel($trip),
            ];
        }

        $waypoint = $trip->waypoints()->orderBy('sequence')->first();

        if ($waypoint && $waypoint->lat !== null && $waypoint->lng !== null) {
            return [
                'type' => 'waypoint',
                'lat' => (float) $waypoint->lat,
                'lng' => (float) $waypoint->lng,
                'label' => $waypoint->label ?: 'Boarding point',
            ];
        }

        return [
            'type' => 'none',
            'lat' => null,
            'lng' => null,
            'label' => $trip->origin_text ?: 'Boarding point',
        ];
    }

    /**
     * Walking distance (metres) from the passenger to the guide target, with
     * the straight-line-to-road factor applied. Null when no pin exists.
     */
    public function walkingDistanceM(float $lat, float $lng, array $target): ?float
    {
        if ($target['lat'] === null || $target['lng'] === null) {
            return null;
        }

        $factor = (float) config('workride.guide.route_factor', 1.25);

        return round(
            $this->geofence->haversine($lat, $lng, (float) $target['lat'], (float) $target['lng']) * $factor,
            0,
        );
    }

    /**
     * Walking ETA in seconds at the configured walking speed.
     */
    public function walkingDurationS(float $distanceM): int
    {
        $speedMs = (float) config('workride.guide.walking_speed_kmh', 5) / 3.6;

        return (int) round($distanceM / $speedMs);
    }

    /**
     * Has the passenger reached the vehicle geofence?
     */
    public function isArrived(float $distanceM): bool
    {
        return $distanceM <= (float) config('workride.guide.arrived_radius_m', 50);
    }

    /**
     * Walking polyline from the passenger's live position to the target.
     *
     * Tries the OSRM walking profile first (free), and falls back to a
     * straight line + haversine ETA when no provider can serve — zero API
     * cost, which is fine for a 200-500 m junction walk.
     *
     * @return array{distance_m:float,duration_s:float,points:array,provider:string}
     */
    public function walkingRoute(array $from, array $to): array
    {
        try {
            $route = $this->routing->route($from, $to, 'foot');

            return [
                'distance_m' => (float) $route['distance_m'],
                'duration_s' => (float) $route['duration_s'],
                'points' => $route['points'],
                'provider' => $route['provider'],
            ];
        } catch (Throwable) {
            $distance = $this->geofence->haversine(
                (float) $from['lat'],
                (float) $from['lng'],
                (float) $to['lat'],
                (float) $to['lng'],
            );

            return [
                'distance_m' => $distance,
                'duration_s' => $this->walkingDurationS($distance),
                'points' => [$from, $to],
                'provider' => 'straight_line',
            ];
        }
    }

    private function vehicleLabel(Trip $trip): string
    {
        $plate = $trip->vehicle?->plate_number;

        return $plate ? "Your ride · {$plate}" : 'Your ride';
    }
}
