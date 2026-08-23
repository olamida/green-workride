<?php

namespace App\Services;

use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Models\GtfsFeedMeta;
use App\Models\GtfsRoute;
use App\Models\GtfsStop;
use App\Models\Trip;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Generates a GTFS static feed (agency/stops/routes/trips/stop_times/calendar/shapes)
 * from the live trips table and the GTFS stop catalog, zips it, and records
 * generation metadata. This is Abuja's first GTFS feed.
 */
class GtfsService
{
    public const SERVICE_ID = 'WR-DAILY';

    public function __construct(private GeofenceService $geofence) {}

    /**
     * Regenerate the GTFS zip and return feed stats.
     *
     * @return array{path:string,size:int,hash:string,stops:int,routes:int,trips:int}
     */
    public function generate(): array
    {
        $routes = $this->ensureRoutes();
        $trips = $this->eligibleTrips();
        $stopsByCorridor = $this->catalogStops();

        $agencyLines = [];
        $stopsLines = $this->catalogStopsCsvLines($stopsByCorridor->flatten());
        $routesLines = [];
        $tripsLines = [];
        $stopTimesLines = [];
        $shapesLines = [];

        $syntheticStops = [];

        foreach ($routes as $route) {
            $routesLines[] = [
                'route_id' => $route->route_id,
                'agency_id' => $route->agency_id,
                'route_short_name' => $route->route_short_name,
                'route_long_name' => $route->route_long_name,
                'route_type' => (string) $route->route_type,
            ];
        }

        foreach ($trips as $trip) {
            $corridor = $trip->corridor;
            $route = $routes->firstWhere('corridor', $corridor->value) ?? $routes->first();
            $tripId = $this->tripIdFor($trip);
            $points = $this->tripPoints($trip, $corridor);
            $times = $this->scheduleTimes($points, $trip->departure_time);

            $corridorStops = $stopsByCorridor->get($corridor->value, collect());

            $stopSequence = [];
            foreach ($points as $index => $point) {
                $resolved = $this->resolveStop($point, $corridor, $corridorStops, $tripId, $index);
                $stopId = $resolved['stop_id'];

                if ($resolved['synthetic']) {
                    $name = $index === 0
                        ? $trip->origin_text
                        : ($index === count($points) - 1 ? $trip->destination_text : $resolved['stop_name']);

                    $syntheticStops[$stopId] = $resolved;
                    $syntheticStops[$stopId]['stop_name'] = $name;
                }

                $stopSequence[] = [
                    'stop_id' => $stopId,
                    'arrival_time' => $times[$index]['arrival'],
                    'departure_time' => $times[$index]['departure'],
                ];
            }

            $tripsLines[] = [
                'route_id' => $route->route_id,
                'service_id' => self::SERVICE_ID,
                'trip_id' => $tripId,
                'trip_headsign' => $trip->destination_text,
                'shape_id' => count($points) >= 2 ? $this->shapeIdFor($trip) : '',
                'wheelchair_accessible' => '0',
            ];

            foreach ($stopSequence as $index => $stopTime) {
                $stopTimesLines[] = [
                    'trip_id' => $tripId,
                    'arrival_time' => $stopTime['arrival_time'],
                    'departure_time' => $stopTime['departure_time'],
                    'stop_id' => $stopTime['stop_id'],
                    'stop_sequence' => (string) ($index + 1),
                ];
            }

            if (count($points) >= 2) {
                foreach ($points as $index => $point) {
                    $shapesLines[] = [
                        'shape_id' => $this->shapeIdFor($trip),
                        'shape_pt_lat' => $this->fmtCoord($point['lat']),
                        'shape_pt_lon' => $this->fmtCoord($point['lng']),
                        'shape_pt_sequence' => (string) ($index + 1),
                    ];
                }
            }
        }

        $agencyLines[] = [
            'agency_id' => config('workride.gtfs.agency_id'),
            'agency_name' => config('workride.gtfs.agency_name'),
            'agency_url' => config('workride.gtfs.agency_url'),
            'agency_timezone' => config('workride.gtfs.agency_timezone'),
            'agency_lang' => config('workride.gtfs.agency_lang'),
        ];

        foreach ($syntheticStops as $stop) {
            $stopsLines[] = [
                'stop_id' => $stop['stop_id'],
                'stop_name' => $stop['stop_name'],
                'stop_lat' => $this->fmtCoord($stop['lat']),
                'stop_lon' => $this->fmtCoord($stop['lng']),
            ];
        }

        $start = CarbonImmutable::today();
        $end = $start->addDays((int) config('workride.gtfs.service_days'));

        $files = [
            'agency.txt' => $this->csv($agencyLines),
            'stops.txt' => $this->csv($stopsLines),
            'routes.txt' => $this->csv($routesLines),
            'trips.txt' => $this->csv($tripsLines),
            'stop_times.txt' => $this->csv($stopTimesLines),
            'calendar.txt' => $this->csv([
                [
                    'service_id' => self::SERVICE_ID,
                    'monday' => '1',
                    'tuesday' => '1',
                    'wednesday' => '1',
                    'thursday' => '1',
                    'friday' => '1',
                    'saturday' => '1',
                    'sunday' => '1',
                    'start_date' => $start->format('Ymd'),
                    'end_date' => $end->format('Ymd'),
                ],
            ]),
            'shapes.txt' => $this->csv($shapesLines),
        ];

        $zipPath = $this->writeZip($files);
        $size = (int) filesize($zipPath);
        $hash = md5_file($zipPath);

        $this->recordMeta($stopsLines, $routesLines, $tripsLines, $size, $hash);

        return [
            'path' => $zipPath,
            'size' => $size,
            'hash' => $hash,
            'stops' => count($stopsLines),
            'routes' => count($routesLines),
            'trips' => count($tripsLines),
        ];
    }

    public function feedPath(): ?string
    {
        $path = $this->publicDisk()->path('gtfs/gtfs.zip');

        return is_file($path) ? $path : null;
    }

    public function metadata(): ?GtfsFeedMeta
    {
        return GtfsFeedMeta::find(1);
    }

    public function tripIdFor(Trip $trip): string
    {
        return 'WR-'.$trip->id;
    }

    public function shapeIdFor(Trip $trip): string
    {
        return 'SHP-'.$trip->id;
    }

    private function eligibleTrips(): EloquentCollection
    {
        return Trip::query()
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::Active])
            ->whereNotNull('departure_time')
            ->orderBy('departure_time')
            ->with('waypoints')
            ->get();
    }

    private function ensureRoutes(): SupportCollection
    {
        $routes = collect();

        foreach (Corridor::cases() as $corridor) {
            $routes->push(GtfsRoute::updateOrCreate(
                ['corridor' => $corridor->value],
                [
                    'route_id' => $corridor->short(),
                    'agency_id' => config('workride.gtfs.agency_id'),
                    'route_short_name' => $corridor->short(),
                    'route_long_name' => $corridor->label(),
                    'route_type' => 3,
                ],
            ));
        }

        return $routes;
    }

    private function catalogStops(): Collection
    {
        return GtfsStop::query()
            ->orderBy('id')
            ->get()
            ->groupBy('corridor');
    }

    private function catalogStopsCsvLines(SupportCollection $stops): array
    {
        return $stops->map(fn (GtfsStop $stop) => [
            'stop_id' => $stop->stop_id,
            'stop_name' => $stop->stop_name,
            'stop_lat' => $this->fmtCoord((float) $stop->stop_lat),
            'stop_lon' => $this->fmtCoord((float) $stop->stop_lon),
        ])->values()->all();
    }

    /**
     * Ordered lat/lng points for a trip. The relational waypoints rows are the
     * source of truth; because the `waypoints` JSON column shadows the relation's
     * property, we read the relation through its query builder. Falls back to the
     * JSON snapshot, then to the corridor endpoints.
     *
     * @return array<int, array{label:string,lat:float,lng:float}>
     */
    private function tripPoints(Trip $trip, Corridor $corridor): array
    {
        $points = [];

        $waypoints = $trip->relationLoaded('waypoints')
            ? $trip->getRelation('waypoints')
            : $trip->waypoints()->orderBy('sequence')->get();

        foreach ($waypoints as $waypoint) {
            $points[] = ['label' => $waypoint->label, 'lat' => (float) $waypoint->lat, 'lng' => (float) $waypoint->lng];
        }

        if (count($points) >= 2) {
            return $points;
        }

        if (is_array($trip->waypoints) && ! empty($trip->waypoints)) {
            foreach ($trip->waypoints as $waypoint) {
                $points[] = ['label' => $waypoint['label'] ?? '', 'lat' => (float) $waypoint['lat'], 'lng' => (float) $waypoint['lng']];
            }
        }

        if (count($points) >= 2) {
            return $points;
        }

        return $this->corridorEndpoints($corridor);
    }

    /**
     * @return array<int, array{label:string,lat:float,lng:float}>
     */
    private function corridorEndpoints(Corridor $corridor): array
    {
        $stops = GtfsStop::where('corridor', $corridor->value)->orderBy('id')->get();

        if ($stops->count() < 2) {
            return [
                ['label' => $corridor->label().' start', 'lat' => 9.0450, 'lng' => 7.4922],
                ['label' => $corridor->label().' end', 'lat' => 9.1000, 'lng' => 7.3300],
            ];
        }

        $start = $stops->first();
        $end = $stops->last();

        return [
            ['label' => $start->stop_name, 'lat' => (float) $start->stop_lat, 'lng' => (float) $start->stop_lon],
            ['label' => $end->stop_name, 'lat' => (float) $end->stop_lat, 'lng' => (float) $end->stop_lon],
        ];
    }

    /**
     * Resolve a trip point to a GTFS stop_id — a nearby catalog stop when within
     * the match radius, otherwise a synthetic stop unique to this trip+index.
     *
     * @param  Collection<int, GtfsStop>  $corridorStops
     * @return array{stop_id:string,stop_name:string,lat:float,lng:float,synthetic:bool}
     */
    private function resolveStop(array $point, Corridor $corridor, Collection $corridorStops, string $tripId, int $index): array
    {
        $radiusM = (float) config('workride.gtfs.stop_match_radius_m');

        $best = null;
        $bestDistance = INF;

        foreach ($corridorStops as $stop) {
            $distance = $this->geofence->haversine($point['lat'], $point['lng'], (float) $stop->stop_lat, (float) $stop->stop_lon);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $stop;
            }
        }

        if ($best !== null && $bestDistance <= $radiusM) {
            return [
                'stop_id' => $best->stop_id,
                'stop_name' => $best->stop_name,
                'lat' => (float) $best->stop_lat,
                'lng' => (float) $best->stop_lon,
                'synthetic' => false,
            ];
        }

        return [
            'stop_id' => sprintf('SYN-%s-%d', $tripId, $index + 1),
            'stop_name' => $point['label'] ?: $corridor->label().' stop '.($index + 1),
            'lat' => $point['lat'],
            'lng' => $point['lng'],
            'synthetic' => true,
        ];
    }

    /**
     * @param  array<int, array{label:string,lat:float,lng:float}>  $points
     * @return array<int, array{arrival:string,departure:string}>
     */
    private function scheduleTimes(array $points, $departureTime): array
    {
        $departure = CarbonImmutable::parse($departureTime);
        $avgSpeedKmh = max(1, (float) config('workride.gtfs.avg_speed_kmh'));

        $legs = [];
        for ($i = 0; $i < count($points) - 1; $i++) {
            $distanceKm = $this->geofence->haversine($points[$i]['lat'], $points[$i]['lng'], $points[$i + 1]['lat'], $points[$i + 1]['lng']) / 1000;
            $legs[] = (int) round(($distanceKm / $avgSpeedKmh) * 3600);
        }

        $times = [];
        $cursor = $departure;

        foreach ($points as $i => $point) {
            if ($i > 0) {
                $cursor = $cursor->addSeconds($legs[$i - 1] ?? 0);
            }

            $times[] = [
                'arrival' => $cursor->format('H:i:s'),
                'departure' => $cursor->format('H:i:s'),
            ];
        }

        return $times;
    }

    private function writeZip(array $files): string
    {
        $disk = $this->publicDisk();
        $disk->makeDirectory('gtfs');

        $path = $disk->path('gtfs/gtfs.zip');

        if (is_file($path)) {
            unlink($path);
        }

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $path;
    }

    private function recordMeta(array $stopsLines, array $routesLines, array $tripsLines, int $size, string $hash): void
    {
        GtfsFeedMeta::updateOrCreate(['id' => 1], [
            'last_generated_at' => now(),
            'stops_count' => count($stopsLines),
            'routes_count' => count($routesLines),
            'trips_count' => count($tripsLines),
            'file_size' => $size,
            'feed_hash' => $hash,
        ]);
    }

    private function publicDisk()
    {
        return Storage::disk('public');
    }

    /**
     * Render an array of associative rows as CSV (header = first row keys).
     */
    private function csv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $header = array_keys($rows[0]);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $header);

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }

    private function fmtCoord(float $value): string
    {
        return number_format($value, 6, '.', '');
    }
}
