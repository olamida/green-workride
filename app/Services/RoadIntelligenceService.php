<?php

namespace App\Services;

use App\Enums\RewardTrigger;
use App\Enums\RoadCondition;
use App\Enums\RoadEventType;
use App\Models\RoadEvent;
use App\Models\RoadSegment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Turns raw accelerometer/GPS samples into confirmed road intelligence.
 *
 * - Pothole confirmation: N reports of the same type within radius_m over
 *   within_hours are clustered → each cluster of min_reports+ is confirmed.
 * - IRI: World Bank RoadLab formula IRI = alpha * RMS(acc_z) / speed + beta,
 *   mapped to condition bands Excellent/Good/Fair/Poor from config.
 * - Segments: per-road_name aggregation of confirmed events → road_segments.
 */
class RoadIntelligenceService
{
    public function __construct(
        private GeofenceService $geofence,
        private RewardService $rewards,
    ) {}

    /**
     * Record one sensor reading. Returns the persisted event, and triggers
     * confirmation clustering + segment aggregation for candidate events.
     */
    public function recordEvent(array $data): RoadEvent
    {
        $event = RoadEvent::create([
            'user_id' => $data['user_id'] ?? null,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'type' => $data['type'],
            'severity' => $data['severity'] ?? 1,
            'speed' => $data['speed'] ?? null,
            'accelerometer_z' => $data['accelerometer_z'] ?? null,
            'road_name' => $data['road_name'] ?? null,
        ]);

        $this->confirmClusters();

        $event->refresh();

        if ($event->type === RoadEventType::Pothole && $event->is_confirmed && $event->road_name) {
            $this->refreshSegment($event->road_name);
        }

        if ($event->is_confirmed && $event->user_id) {
            $reporter = User::find($event->user_id);

            if ($reporter) {
                $this->rewards->award(
                    $reporter,
                    RewardTrigger::PotholeConfirmed,
                    ['event_key' => "road-event-{$event->id}", 'road_event_id' => $event->id],
                );
            }
        }

        return $event;
    }

    /**
     * Group unconfirmed events into clusters of the same type within
     * radius_m metres and within_hours of each other. Any cluster with
     * >= min_reports members is confirmed (including every member).
     *
     * @return int number of events newly confirmed
     */
    public function confirmClusters(): int
    {
        $radius = (float) config('workride.pothole_confirm.radius_m', 20);
        $hours = (int) config('workride.pothole_confirm.within_hours', 72);
        $minReports = (int) config('workride.pothole_confirm.min_reports', 5);

        $candidates = RoadEvent::query()
            ->where('is_confirmed', false)
            ->whereBetween('created_at', [now()->subHours($hours), now()])
            ->orderBy('created_at')
            ->get();

        $toConfirm = [];

        foreach ($candidates as $anchor) {
            if (isset($toConfirm[$anchor->id])) {
                continue;
            }

            $cluster = $candidates->filter(
                fn (RoadEvent $other) => $this->geofence->haversine(
                    (float) $anchor->lat,
                    (float) $anchor->lng,
                    (float) $other->lat,
                    (float) $other->lng,
                ) <= $radius
            );

            if ($cluster->count() >= $minReports) {
                foreach ($cluster as $member) {
                    $toConfirm[$member->id] = $member->id;
                }
            }
        }

        if (empty($toConfirm)) {
            return 0;
        }

        $affected = RoadEvent::whereIn('id', array_keys($toConfirm))
            ->where('is_confirmed', false)
            ->update(['is_confirmed' => true]);

        foreach (array_keys($toConfirm) as $id) {
            $event = $candidates->firstWhere('id', $id);

            if ($event && $event->type === RoadEventType::Pothole && $event->road_name) {
                try {
                    $this->refreshSegment($event->road_name);
                } catch (Throwable) {
                    // Segment refresh is best-effort; never block event confirm.
                }
            }
        }

        return (int) $affected;
    }

    /**
     * Compute the RoadLab IRI estimate for one sample.
     */
    public function iri(?float $accelerometerZ, ?float $speedKmh): ?float
    {
        if ($accelerometerZ === null) {
            return null;
        }

        $alpha = (float) config('workride.road_sensor.iri_alpha', 2.0);
        $beta = (float) config('workride.road_sensor.iri_beta', 1.5);
        $speed = max($speedKmh ?? 1, 1);
        $speed = min($speed, (float) config('workride.road_sensor.max_speed_kmh', 200));

        return round($alpha * sqrt($accelerometerZ ** 2) / $speed + $beta, 2);
    }

    public function conditionFor(float $iri): RoadCondition
    {
        $thresholds = config('workride.iri_thresholds');

        return match (true) {
            $iri < $thresholds['excellent'] => RoadCondition::Excellent,
            $iri < $thresholds['good'] => RoadCondition::Good,
            $iri < $thresholds['fair'] => RoadCondition::Fair,
            default => RoadCondition::Poor,
        };
    }

    /**
     * Recompute a road segment's IRI from its confirmed pothole events.
     */
    public function refreshSegment(string $roadName): ?RoadSegment
    {
        $events = RoadEvent::query()
            ->where('road_name', $roadName)
            ->where('is_confirmed', true)
            ->whereNotNull('accelerometer_z')
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        $iris = $events
            ->map(fn (RoadEvent $event) => $this->iri((float) $event->accelerometer_z, $event->speed !== null ? (float) $event->speed : null))
            ->filter();

        if ($iris->isEmpty()) {
            return null;
        }

        $avgIri = round($iris->avg(), 2);

        return RoadSegment::updateOrCreate(
            ['road_name' => $roadName],
            [
                'avg_iri' => $avgIri,
                'condition' => $this->conditionFor($avgIri),
                'last_updated' => now(),
            ]
        );
    }

    /**
     * Confirmed potholes for the public heatmap — lat/lng/severity only.
     */
    public function confirmedPotholes(?int $hours = null): Collection
    {
        return RoadEvent::query()
            ->where('type', RoadEventType::Pothole)
            ->where('is_confirmed', true)
            ->when($hours, fn ($q) => $q->where('created_at', '>=', now()->subHours($hours)))
            ->latest()
            ->get(['id', 'lat', 'lng', 'type', 'severity', 'is_confirmed', 'road_name', 'created_at']);
    }

    /**
     * Road segments by condition band, for the ops dashboard pie.
     */
    public function segmentsByCondition(): Collection
    {
        return RoadSegment::query()
            ->select('condition', DB::raw('count(*) as total'))
            ->groupBy('condition')
            ->get();
    }

    /**
     * FERMA-ready CSV rows for confirmed potholes.
     *
     * @return array<int, array<string, string|float>>
     */
    public function fermaExport(): array
    {
        return $this->confirmedPotholes()
            ->map(fn (RoadEvent $event) => [
                'road_name' => $event->road_name ?? 'unknown',
                'lat' => (float) $event->lat,
                'lng' => (float) $event->lng,
                'type' => $event->type->value,
                'severity' => $event->severity,
                'reported_at' => $event->created_at->toDateTimeString(),
            ])
            ->values()
            ->all();
    }
}
