<?php

namespace App\Services;

use App\Exceptions\RoutingUnavailableException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Routing with an open-source-first strategy:
 *
 *   1. OSRM (self-hosted, free) — the default primary.
 *   2. Google Directions — paid fallback, only when enabled AND the monthly
 *      API budget has headroom (CostLogger::withinMonthlyCap). Every call is
 *      logged to api_cost_logs with its naira cost.
 *   3. Mapbox — same capped+logged treatment.
 *
 * This keeps infrastructure cost at ~₦0 while retaining a reliability net.
 */
class RoutingService
{
    public function __construct(private CostLogger $costs) {}

    /**
     * Route from A to B. Returns [distance_m, duration_s, points, provider].
     *
     * The connect guide (last-mile walking) calls this with `foot` so the
     * OSRM host serves its walking profile; Google/Mapbox map it to their
     * walking modes. Default `driving` keeps existing callers unchanged.
     *
     * @param  array{lat:float,lng:float}  $from
     * @param  array{lat:float,lng:float}  $to
     * @param  string  $profile  driving|foot
     * @return array{distance_m:float,duration_s:float,points:array,provider:string}
     */
    public function route(array $from, array $to, string $profile = 'driving'): array
    {
        $primary = config('workride.routing.primary', 'osrm');

        $strategies = match ($primary) {
            'google' => [$this->viaGoogle(...), $this->viaMapbox(...), $this->viaOsrm(...)],
            'mapbox' => [$this->viaMapbox(...), $this->viaGoogle(...), $this->viaOsrm(...)],
            default => [$this->viaOsrm(...), $this->viaGoogle(...), $this->viaMapbox(...)],
        };

        $lastError = null;

        foreach ($strategies as $strategy) {
            try {
                return $strategy($from, $to, $profile);
            } catch (Throwable $e) {
                $lastError = $e;
            }
        }

        throw new RoutingUnavailableException(
            'No routing provider available: '.($lastError?->getMessage() ?? 'all providers failed.'),
            previous: $lastError,
        );
    }

    /**
     * Free, open-source geocoding fallback (Nominatim / OSM) for the
     * navigation search box. OSRM has no geocoder, so this is a separate
     * provider rather than another routing strategy. Always returns a plain
     * list — never throws — so the caller can fall back to "no results".
     *
     * @param  array{lat:float,lng:float}|null  $near  optional centre point for result ordering
     * @return array<int, array{name:string, lat:float, lng:float, type:string, corridor:?string, passenger_volume_daily:?int}>
     */
    public function geocode(string $query, ?array $near = null): array
    {
        $base = rtrim((string) config('workride.routing.nominatim_base_url'), '/');
        $countrycodes = (string) config('workride.routing.geocode_countrycodes', 'ng');

        try {
            $response = Http::timeout((int) config('workride.routing.osrm_timeout', 5))
                ->withHeaders([
                    'User-Agent' => 'WorkRide/1.0 (+https://workride.ng) — open transit research',
                    'Accept-Language' => 'en',
                ])
                ->get($base.'/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 5,
                    'countrycodes' => $countrycodes,
                ]);

            $response->throw();

            $results = collect((array) $response->json());

            if ($near) {
                $results = $results->sortBy(fn (array $r) => $this->haversine(
                    (float) $near['lat'],
                    (float) $near['lng'],
                    (float) $r['lat'],
                    (float) $r['lon'],
                ))->values();
            }

            $this->costs->log('nominatim', 'geocode', 0.0, [
                'query' => $query,
                'count' => $results->count(),
            ]);

            return $results->map(fn (array $r) => [
                'name' => $r['name'] ?? $r['display_name'] ?? $query,
                'lat' => (float) $r['lat'],
                'lng' => (float) $r['lon'],
                'type' => 'geocode',
                'corridor' => null,
                'passenger_volume_daily' => null,
            ])->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Self-hosted OSRM — free, preferred. Logged with cost 0 for the audit trail.
     */
    private function viaOsrm(array $from, array $to, string $profile = 'driving'): array
    {
        $host = rtrim((string) config('workride.routing.osrm_host'), '/');
        $url = $host.'/route/v1/'.$profile.'/'.$from['lng'].','.$from['lat'].';'.$to['lng'].','.$to['lat'];

        $response = Http::timeout((int) config('workride.routing.osrm_timeout', 5))
            ->get($url, ['overview' => 'full', 'geometries' => 'geojson']);

        $response->throw();

        $body = $response->json();
        $route = $body['routes'][0] ?? null;

        if (! $route) {
            throw new RoutingUnavailableException('OSRM returned no route.');
        }

        $points = array_map(
            fn (array $coordinate) => ['lat' => $coordinate[1], 'lng' => $coordinate[0]],
            $route['geometry']['coordinates'] ?? [],
        );

        $this->costs->log('osrm', 'routing', 0.0, [
            'from' => $from,
            'to' => $to,
            'distance_m' => $route['distance'],
            'duration_s' => $route['duration'],
            'profile' => $profile,
        ]);

        return [
            'distance_m' => (float) $route['distance'],
            'duration_s' => (float) $route['duration'],
            'points' => $points,
            'provider' => 'osrm',
        ];
    }

    /**
     * Google Directions — paid fallback. Refused without budget headroom.
     */
    private function viaGoogle(array $from, array $to, string $profile = 'driving'): array
    {
        $this->assertFallbackAvailable('use_google_fallback', 'google_api_key', 'Google Directions');

        $costPerRequest = (float) config('workride.routing.google_cost_per_request', 20);

        if (! $this->costs->withinMonthlyCap($costPerRequest)) {
            throw new RoutingUnavailableException('Monthly API budget reached — Google fallback refused.');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => $from['lat'].','.$from['lng'],
            'destination' => $to['lat'].','.$to['lng'],
            'key' => config('workride.routing.google_api_key'),
            'mode' => $profile === 'foot' ? 'walking' : 'driving',
        ]);

        $response->throw();

        $body = $response->json();
        $leg = $body['routes'][0]['legs'][0] ?? null;

        if (! $leg) {
            throw new RoutingUnavailableException('Google Directions returned no route.');
        }

        $this->costs->log('google_directions', 'routing', $costPerRequest, [
            'from' => $from,
            'to' => $to,
            'distance_m' => $leg['distance']['value'],
            'duration_s' => $leg['duration']['value'],
            'profile' => $profile,
        ]);

        return [
            'distance_m' => (float) $leg['distance']['value'],
            'duration_s' => (float) $leg['duration']['value'],
            'points' => $this->decodePolyline($body['routes'][0]['overview_polyline']['points'] ?? ''),
            'provider' => 'google_directions',
        ];
    }

    /**
     * Mapbox Directions — optional premium fallback, capped+logged.
     */
    private function viaMapbox(array $from, array $to, string $profile = 'driving'): array
    {
        $this->assertFallbackAvailable('use_mapbox_premium', 'mapbox_access_token', 'Mapbox');

        $costPerRequest = (float) config('workride.routing.mapbox_cost_per_request', 25);

        if (! $this->costs->withinMonthlyCap($costPerRequest)) {
            throw new RoutingUnavailableException('Monthly API budget reached — Mapbox fallback refused.');
        }

        $mapboxProfile = $profile === 'foot' ? 'walking' : 'driving';

        $response = Http::get(
            'https://api.mapbox.com/directions/v5/mapbox/'.$mapboxProfile.'/'.$from['lng'].','.$from['lat'].';'.$to['lng'].','.$to['lat'],
            [
                'access_token' => config('workride.routing.mapbox_access_token'),
                'geometries' => 'geojson',
                'overview' => 'full',
            ]
        );

        $response->throw();

        $body = $response->json();
        $route = $body['routes'][0] ?? null;

        if (! $route) {
            throw new RoutingUnavailableException('Mapbox returned no route.');
        }

        $points = array_map(
            fn (array $coordinate) => ['lat' => $coordinate[1], 'lng' => $coordinate[0]],
            $route['geometry']['coordinates'] ?? [],
        );

        $this->costs->log('mapbox', 'routing', $costPerRequest, [
            'from' => $from,
            'to' => $to,
            'distance_m' => $route['distance'],
            'duration_s' => $route['duration'],
            'profile' => $profile,
        ]);

        return [
            'distance_m' => (float) $route['distance'],
            'duration_s' => (float) $route['duration'],
            'points' => $points,
            'provider' => 'mapbox',
        ];
    }

    private function assertFallbackAvailable(string $enabledKey, string $credentialKey, string $name): void
    {
        if (! config('workride.routing.'.$enabledKey)) {
            throw new RoutingUnavailableException($name.' fallback is disabled.');
        }

        if (empty(config('workride.routing.'.$credentialKey))) {
            throw new RoutingUnavailableException($name.' credentials missing.');
        }
    }

    /**
     * Decode Google's encoded polyline into [[lat,lng], ...].
     */
    private function decodePolyline(string $encoded): array
    {
        $points = [];
        $index = 0;
        $len = strlen($encoded);
        $lat = 0;
        $lng = 0;

        while ($index < $len) {
            $b = 0;
            $shift = 0;
            $result = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1F) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $dlat = ($result & 1) !== 0 ? ~($result >> 1) : ($result >> 1);
            $lat += $dlat;

            $shift = 0;
            $result = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1F) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $dlng = ($result & 1) !== 0 ? ~($result >> 1) : ($result >> 1);
            $lng += $dlng;

            $points[] = ['lat' => $lat / 1e5, 'lng' => $lng / 1e5];
        }

        return $points;
    }
}
