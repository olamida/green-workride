<?php

namespace App\Services;

use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Models\DriverScore;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

/**
 * Corridor + proximity + departure-window matching.
 *
 * Trips are fetched within the corridor and departure window, then scored by
 * Haversine distance from the passenger's pickup point (falling back to the
 * trip origin) and departure time.
 */
class TripMatchingService
{
    public function __construct(
        private GeofenceService $geofence,
        private RatingService $ratings,
    ) {}

    /**
     * @return Collection<int, Trip>
     */
    public function findMatches(
        float $fromLat,
        float $fromLng,
        ?Corridor $corridor = null,
        ?int $withinMinutes = null,
    ): Collection {
        $radiusMeters = (int) config('workride.search_radius_m', 2000);

        return $this->upcoming($corridor, $withinMinutes)
            ->map(function (Trip $trip) use ($fromLat, $fromLng) {
                $trip->match_distance_m = $this->distanceToTrip($trip, $fromLat, $fromLng);

                return $trip;
            })
            ->filter(fn (Trip $trip) => $trip->match_distance_m <= $radiusMeters)
            ->map(function (Trip $trip) use ($fromLat, $fromLng, $radiusMeters, $withinMinutes) {
                $scored = $this->scoreTrip($trip, $fromLat, $fromLng, $radiusMeters, $withinMinutes);
                $trip->match_score = $scored['score'];
                $trip->score_reasons = $scored['reasons'];

                return $trip;
            })
            ->sortBy(fn (Trip $trip) => [
                -(int) ($trip->match_score ?? 0),
                $trip->match_distance_m,
                $trip->departure_time->timestamp,
            ])
            ->values();
    }

    /**
     * Upcoming bookable trips in the departure window, optionally by corridor.
     * Used by the web board where a passenger pickup point may not be known.
     *
     * Live (active) trips always sort first, then soonest departure — the
     * "leaving soon" boost so nothing already on the road gets buried under
     * tomorrow's schedule. Each trip carries a `leaving_soon` flag for the
     * board badge (departing within 30 minutes).
     *
     * @param  bool|null  $womenOnly  when true, only women-only trips are returned
     * @return Collection<int, Trip>
     */
    public function upcoming(?Corridor $corridor = null, ?int $withinMinutes = null, ?bool $womenOnly = null): Collection
    {
        $withinMinutes ??= (int) config('workride.departure_window_minutes', 30);
        $leavingSoonMinutes = (int) config('workride.departure_window_minutes', 30);

        $trips = Trip::query()
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::Active])
            ->where('available_seats', '>', 0)
            ->whereBetween('departure_time', [now(), now()->addMinutes($withinMinutes)])
            ->when($corridor, fn ($query) => $query->where('corridor', $corridor))
            ->when($womenOnly, fn ($query) => $query->where('women_only', true))
            ->with(['driver', 'vehicle'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('departure_time')
            ->get();

        $leavingCutoff = now()->addMinutes($leavingSoonMinutes);

        $trips->each(function (Trip $trip) use ($leavingCutoff) {
            $trip->leaving_soon = $trip->status === TripStatus::Active
                || ($trip->departure_time !== null && $trip->departure_time->lte($leavingCutoff));
        });

        $this->ratings->attachDriverRatingToTrips($trips);
        DriverScore::attachLatestToTrips($trips);

        // Board context has no passenger pickup point, so the score drops the
        // proximity factor and ranks purely on timing + driver quality. The API
        // matcher re-scores each trip WITH proximity before sorting (§findMatches).
        $trips->each(function (Trip $trip) {
            $scored = $this->scoreTrip($trip);
            $trip->match_score = $scored['score'];
            $trip->score_reasons = $scored['reasons'];
        });

        return $trips;
    }

    /**
     * Corridors with live or "leaving soon" trips right now, keyed by corridor
     * value. Drives the soft pulse on the board's corridor chips so a rider can
     * see at a glance where something is moving without clicking through.
     *
     * @return array<string, bool>
     */
    public function liveCorridors(): array
    {
        $leavingSoonMinutes = (int) config('workride.departure_window_minutes', 30);

        $rows = Trip::query()
            ->select('corridor')
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::Active])
            ->where('available_seats', '>', 0)
            ->where('departure_time', '<=', now()->addMinutes($leavingSoonMinutes))
            ->where('departure_time', '>=', now())
            ->distinct()
            ->pluck('corridor');

        return $rows->mapWithKeys(fn ($corridor) => [
            $corridor instanceof Corridor ? $corridor->value : (string) $corridor => true,
        ])->all();
    }

    /**
     * Per-corridor availability within a board window: how many trips are
     * bookable and what the cheapest seat costs. Drives the corridor chip
     * hero on the board ("Kubwa→CBD · 3 · ₦600") so a rider sees where
     * supply exists at a glance, without clicking through every corridor.
     *
     * @return array<string, array{count:int, min_fare:?int}>
     */
    public function corridorStats(int $withinMinutes): array
    {
        $rows = Trip::query()
            ->select('corridor')
            ->selectRaw('COUNT(*) as trip_count')
            ->selectRaw('MIN(fare_per_seat) as min_fare')
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::Active])
            ->where('available_seats', '>', 0)
            ->whereBetween('departure_time', [now(), now()->addMinutes($withinMinutes)])
            ->groupBy('corridor')
            ->get();

        return $rows->mapWithKeys(function ($row) {
            $corridor = $row->corridor;

            return [
                $corridor instanceof Corridor ? $corridor->value : (string) $corridor => [
                    'count' => (int) $row->trip_count,
                    'min_fare' => $row->min_fare !== null ? (int) round((float) $row->min_fare) : null,
                ],
            ];
        })->all();
    }

    /**
     * Distance from the passenger's pickup point to the trip's live location,
     * falling back to the trip origin when no live location has been reported.
     */
    public function distanceToTrip(Trip $trip, float $fromLat, float $fromLng): float
    {
        $tripLat = $trip->current_lat ? (float) $trip->current_lat : null;
        $tripLng = $trip->current_lng ? (float) $trip->current_lng : null;

        return $this->geofence->haversine($fromLat, $fromLng, $tripLat ?? 9.05, $tripLng ?? 7.45);
    }

    /**
     * Weighted 0-100 match score with human-readable reasons (v6 matching-polish).
     *
     * Weights come from config('workride.matching.score_weights') and sum to 100.
     * Proximity only scores when a passenger pickup point is supplied — the board
     * (no pickup) ranks purely on timing + driver quality, the API matcher adds
     * the distance factor. Reasons explain the score so riders trust the ranking.
     *
     * @return array{score:int, reasons:string[]}
     */
    public function scoreTrip(
        Trip $trip,
        ?float $fromLat = null,
        ?float $fromLng = null,
        ?float $radiusMeters = null,
        ?int $withinMinutes = null,
    ): array {
        $weights = (array) config('workride.matching.score_weights', []);
        $radiusMeters ??= (float) config('workride.search_radius_m', 2000);
        $withinMinutes ??= (int) config('workride.departure_window_minutes', 30);

        $score = 0;
        $reasons = [];

        // --- proximity (40) — only when we know where the passenger is ---
        if ($fromLat !== null && $fromLng !== null) {
            $distance = $this->distanceToTrip($trip, $fromLat, $fromLng);
            $ratio = max(0.0, 1 - ($distance / $radiusMeters));
            $score += (float) ($weights['proximity'] ?? 40) * $ratio;
            $reasons[] = '≈ '.max(1, (int) round($distance)).' km from you';
        }

        // --- timing (25) — live trips are ideal; soonest departure wins ---
        $isLive = $trip->status === TripStatus::Active;
        $minutesToDeparture = $isLive ? 0 : max(0, (int) $trip->departure_time?->diffInMinutes(now(), false) ?? 0);
        $timingRatio = $isLive ? 1.0 : max(0.0, 1 - ($minutesToDeparture / $withinMinutes));
        $score += (float) ($weights['timing'] ?? 25) * $timingRatio;
        $reasons[] = $isLive
            ? 'Live now'
            : ($minutesToDeparture === 0
                ? 'Leaves now'
                : 'Leaves in '.$minutesToDeparture.' min');

        // --- rating (15) — rated drivers out-rank new ones; no rating is neutral ---
        $rating = $trip->driver_rating_avg !== null ? (float) $trip->driver_rating_avg : 0.0;
        $ratingCount = (int) ($trip->driver_rating_count ?? 0);
        $ratingRatio = $ratingCount > 0 ? $rating / 5 : 0.5;
        $score += (float) ($weights['rating'] ?? 15) * $ratingRatio;
        $reasons[] = $ratingCount > 0
            ? '★ '.number_format($rating, 1).' driver ('.$ratingCount.')'
            : 'New driver';

        // --- verification (10) — Level 3 is the fully-checked paid driver ---
        $level = $trip->driver?->verification_level;
        $verificationRatio = $level?->canDrivePaid() ? 1.0 : ($level?->canDriveVolunteer() ? 0.6 : 0.2);
        $score += (float) ($weights['verification'] ?? 10) * $verificationRatio;
        $reasons[] = $level?->canDrivePaid()
            ? 'Level 3 verified'
            : ($level?->canDriveVolunteer() ? 'Workplace verified' : 'New account');

        // --- seat_fill (10) — plenty of room beats a nearly-full bus ---
        $fillRatio = $trip->total_seats > 0 ? ($trip->available_seats / $trip->total_seats) : 0.5;
        $score += (float) ($weights['seat_fill'] ?? 10) * $fillRatio;
        $reasons[] = $trip->available_seats.' seats free';

        // Free volunteer rides get an honest label (no points — fare is the hook).
        if ($trip->is_free_volunteer) {
            $reasons[] = 'FREE ride';
        }

        return [
            'score' => min(100, max(0, (int) round($score))),
            'reasons' => $reasons,
        ];
    }
}
