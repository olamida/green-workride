<?php

namespace App\Services;

use App\Enums\DemandDayType;
use App\Enums\DemandRequestStatus;
use App\Models\DemandRequest;
use App\Models\DemandSurvey;
use App\Models\Junction;
use App\Models\OdMatrix;
use App\Models\OdSurvey;
use App\Models\ProbeDemandPoint;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Demand research (guide §9B — BRT pre-design method with phones, not
 * consultants). Four signals feed one heatmap: manual junction counts,
 * probe-car dwell points, workplace OD surveys and rider check-ins.
 */
class DemandService
{
    public function __construct(private GeofenceService $geofence) {}

    /**
     * Record one manual junction count (NYSC "survey mode").
     */
    public function recordSurvey(User $collector, array $data): DemandSurvey
    {
        $junction = Junction::find($data['junction_id']);

        if (! $junction) {
            throw ValidationException::withMessages(['junction_id' => 'Unknown junction.']);
        }

        return DemandSurvey::create([
            'junction_id' => $junction->id,
            'count' => (int) $data['count'],
            'destination_text' => $data['destination_text'] ?? null,
            'hour' => isset($data['hour']) ? (int) $data['hour'] : (int) now()->hour,
            'day_type' => $data['day_type'] ?? DemandDayType::Weekday,
            'weather' => $data['weather'] ?? null,
            'collected_by' => $collector->id,
            'lat' => $data['lat'] ?? $junction->lat,
            'lng' => $data['lng'] ?? $junction->lng,
            'photo_path' => $data['photo_path'] ?? null,
        ]);
    }

    /**
     * Per-junction totals for the Control Tower demand map.
     */
    public function junctionCounts(): array
    {
        return Junction::withCount('surveys as surveys_count')
            ->with(['surveys' => fn ($q) => $q->select('junction_id', 'count', 'day_type', 'hour', 'destination_text')])
            ->where('is_active', true)
            ->orderByDesc('surveys_count')
            ->get()
            ->map(fn (Junction $j) => [
                'id' => $j->id,
                'name' => $j->name,
                'corridor' => $j->corridor,
                'zone' => $j->zone,
                'lat' => $j->lat,
                'lng' => $j->lng,
                'count' => $j->totalCounted(),
                'surveys' => $j->surveys_count,
                'destinations' => $j->surveys
                    ->groupBy('destination_text')
                    ->mapWithKeys(fn ($rows, $dest) => [$dest => (int) $rows->sum('count')])
                    ->sortDesc()
                    ->take(3)
                    ->all(),
            ])
            ->all();
    }

    /**
     * Merge one probe reading into the nearest point (150 m) or create it.
     * Slowness = waiting people = demand. Radius matching runs a portable
     * bounding box in SQL then a precise haversine check in PHP (SQRT/POW in
     * raw SQL is not supported on SQLite).
     */
    public function recordProbePoint(array $data): ProbeDemandPoint
    {
        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];
        $radius = 0.0015; // ~150 m

        $candidates = ProbeDemandPoint::query()
            ->whereBetween('lat', [$lat - $radius, $lat + $radius])
            ->whereBetween('lng', [$lng - $radius, $lng + $radius])
            ->latest('last_seen_at')
            ->get();

        $point = $candidates->first(fn (ProbeDemandPoint $p) => $this->haversine($lat, $lng, (float) $p->lat, (float) $p->lng) < 150);

        if ($point) {
            $point->update([
                'avg_speed' => ((float) $point->avg_speed * $point->times_visited + (float) $data['avg_speed']) / ($point->times_visited + 1),
                'dwell_time_seconds' => max((int) $point->dwell_time_seconds, (int) $data['dwell_time_seconds']),
                'times_visited' => $point->times_visited + 1,
                'last_seen_at' => now(),
                'corridor' => $data['corridor'] ?? $point->corridor,
            ]);

            return $point;
        }

        return ProbeDemandPoint::create([
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'corridor' => $data['corridor'] ?? null,
            'avg_speed' => $data['avg_speed'],
            'dwell_time_seconds' => $data['dwell_time_seconds'],
            'times_visited' => 1,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Rider check-in ("I'm at Berger, need a ride to Secretariat, 2 people").
     */
    public function checkIn(User $user, array $data): DemandRequest
    {
        if ($data['pickup_lat'] !== null && ! $this->geofence->isInsideFct((float) $data['pickup_lat'], (float) $data['pickup_lng'])) {
            throw ValidationException::withMessages(['pickup_lat' => 'Check-ins must be inside the FCT.']);
        }

        return DemandRequest::create([
            'user_id' => $user->id,
            'pickup_lat' => $data['pickup_lat'],
            'pickup_lng' => $data['pickup_lng'],
            'destination_text' => $data['destination_text'],
            'passengers_count' => (int) ($data['passengers_count'] ?? 1),
            'requested_at' => now(),
            'status' => DemandRequestStatus::Pending,
        ]);
    }

    /**
     * Snapshot the OD matrix from completed OD surveys (guide §9B Method 3).
     * Period filters limit the window; results are stored per corridor when known.
     */
    public function generateOdMatrix(array $data): int
    {
        $start = $data['period_start'] ?? now()->startOfMonth()->toDateString();
        $end = $data['period_end'] ?? now()->endOfMonth()->toDateString();

        // The destination of every OD survey is the workplace the respondent
        // commutes to — there is no destination_area column on od_surveys.
        $rows = OdSurvey::query()
            ->whereBetween('od_surveys.created_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->join('workplaces', 'od_surveys.workplace_id', '=', 'workplaces.id')
            ->selectRaw('home_area as origin_area, workplaces.zone as destination_area, COUNT(*) as count')
            ->groupBy('origin_area', 'destination_area')
            ->get();

        $destinations = [
            'Central Business District' => 'CBD',
            'CBD' => 'CBD',
            'Federal Secretariat' => 'CBD',
            'Secretariat' => 'CBD',
            'Wuse' => 'Wuse',
            'Garki' => 'CBD',
            'Idu' => 'Idu',
        ];

        foreach ($rows as $row) {
            $row->destination_area = $destinations[$row->destination_area] ?? $row->destination_area;
        }

        $rows = $rows->groupBy(fn ($r) => $r->origin_area.'|'.$r->destination_area);

        OdMatrix::whereBetween('period_start', [$start, $end])->delete();

        foreach ($rows as $key => $group) {
            [$origin, $destination] = explode('|', $key);

            OdMatrix::create([
                'origin_area' => $origin,
                'destination_area' => $destination,
                'count' => (int) $group->sum('count'),
                'period_start' => $start,
                'period_end' => $end,
                'generated_by' => $data['generated_by'] ?? null,
            ]);
        }

        return OdMatrix::whereDate('period_start', $start)->count();
    }

    /**
     * Pending rider check-ins (the "12 people at Nyanya now" dispatch signal).
     */
    public function pendingRequests(): int
    {
        return DemandRequest::where('status', DemandRequestStatus::Pending)->count();
    }

    /**
     * Board demand snapshot: how many people want a ride right now (last 24h
     * pending check-ins) and where they want to go. Drives the demand-aware
     * empty state + the "How to book" live strip on the trip board.
     */
    public function demandSnapshot(): array
    {
        $rows = DemandRequest::query()
            ->where('status', DemandRequestStatus::Pending)
            ->where('requested_at', '>=', now()->subDay())
            ->selectRaw('COALESCE(NULLIF(TRIM(destination_text), ""), "the CBD") as destination, SUM(passengers_count) as total')
            ->groupBy('destination')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        return [
            'people' => (int) $rows->sum('total'),
            'top_destinations' => $rows->pluck('destination')->all(),
        ];
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->geofence->haversine($lat1, $lng1, $lat2, $lng2);
    }
}
