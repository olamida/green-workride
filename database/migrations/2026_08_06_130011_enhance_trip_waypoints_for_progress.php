<?php

use App\Models\Trip;
use App\Models\TripWaypoint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 3 — live junction progress.
 *
 * Adds the timing/geofence columns the progress tracker reads:
 *   eta_minutes            ETA from origin (minutes)
 *   is_major_hub           flag for named junctions (Berger, Banex, …)
 *   distance_from_origin_km cumulative distance from the first waypoint
 *   geofence_radius_m      arrival radius used by calculateProgress()
 *   reached_at             timestamp set by WaypointReached
 *
 * The backfill is idempotent: JSON waypoints that never got relational rows
 * are created, and every row is stamped with distance/ETA/hub derived from
 * the first waypoint. Re-running migrate does not re-run this closure.
 */
return new class extends Migration
{
    /** Labels that count as a named junction hub for the tracker badge. */
    private const MAJOR_HUBS = [
        'Kubwa', 'Berger', 'Banex', 'Wuse', 'Jabi', 'Gwarinpa', 'Mabushi',
        'Maitama', 'Garki', 'Central', 'City Gate', 'Zuba', 'Nyanya', 'Lugbe',
        'Area 1', 'Area 3', 'Area 10', 'Karu', 'Gwarinpa',
    ];

    public function up(): void
    {
        Schema::table('trip_waypoints', function (Blueprint $table) {
            $table->integer('eta_minutes')->nullable()->comment('ETA from origin in minutes');
            $table->boolean('is_major_hub')->default(false);
            $table->decimal('distance_from_origin_km', 6, 2)->nullable();
            $table->integer('geofence_radius_m')->default(100);
            $table->timestamp('reached_at')->nullable();
        });

        $this->backfillProgress();
    }

    public function down(): void
    {
        Schema::table('trip_waypoints', function (Blueprint $table) {
            $table->dropColumn([
                'eta_minutes',
                'is_major_hub',
                'distance_from_origin_km',
                'geofence_radius_m',
                'reached_at',
            ]);
        });
    }

    /**
     * Ensure every JSON waypoint has a relational row, then stamp distance,
     * ETA and hub flags using the first waypoint as the origin.
     *
     * Sequence bases differ by origin: TripService::publish() persists 1-based
     * rows (JSON index + 1); the rich seeders persisted 0-based rows (JSON
     * index directly). The base is inferred from the lowest existing sequence,
     * so this handles both without guessing.
     */
    private function backfillProgress(): void
    {
        $speedKmh = (float) config('workride.gtfs.avg_speed_kmh', 30);

        Trip::query()
            ->whereNotNull('waypoints')
            ->chunkById(100, function ($trips) use ($speedKmh) {
                foreach ($trips as $trip) {
                    $json = $trip->waypoints;
                    if (! is_array($json) || empty($json)) {
                        continue;
                    }

                    $rows = $trip->waypoints()
                        ->orderBy('sequence')
                        ->get();

                    $base = $rows->min('sequence');
                    $base = $base === 0 ? 0 : 1;
                    $maxExpected = $base + count($json) - 1;

                    // Self-heal out-of-range rows (spurious duplicates) so a
                    // re-run converges to exactly one row per JSON entry.
                    $stale = $rows->filter(
                        fn (TripWaypoint $row) => $row->sequence < $base || $row->sequence > $maxExpected
                    );
                    if ($stale->isNotEmpty()) {
                        TripWaypoint::query()->whereIn('id', $stale->pluck('id'))->delete();
                    }

                    foreach ($json as $index => $entry) {
                        $sequence = $index + $base;
                        $row = $rows->first(fn (TripWaypoint $r) => $r->sequence === $sequence);

                        if (! $row) {
                            $row = $trip->waypoints()->create([
                                'label' => $entry['label'] ?? '',
                                'lat' => $entry['lat'] ?? 0,
                                'lng' => $entry['lng'] ?? 0,
                                'sequence' => $sequence,
                            ]);
                        }

                        $row->update($this->progressColumns($row, $speedKmh));
                    }
                }
            });
    }

    private function progressColumns(TripWaypoint $waypoint, float $speedKmh): array
    {
        $first = TripWaypoint::query()
            ->where('trip_id', $waypoint->trip_id)
            ->orderBy('sequence')
            ->first();

        $distanceKm = $first
            ? round($this->haversineKm(
                (float) $first->lat,
                (float) $first->lng,
                (float) $waypoint->lat,
                (float) $waypoint->lng,
            ), 2)
            : null;

        return [
            'eta_minutes' => $distanceKm !== null && $speedKmh > 0
                ? (int) round(($distanceKm / $speedKmh) * 60)
                : null,
            'is_major_hub' => $this->isMajorHub($waypoint->label),
            'distance_from_origin_km' => $distanceKm,
        ];
    }

    private function isMajorHub(string $label): bool
    {
        foreach (self::MAJOR_HUBS as $hub) {
            if (str_contains(strtolower($label), strtolower($hub))) {
                return true;
            }
        }

        return false;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
};
