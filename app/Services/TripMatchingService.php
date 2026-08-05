<?php

namespace App\Services;

use App\Enums\Corridor;
use App\Enums\TripStatus;
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
            ->sortBy(fn (Trip $trip) => [$trip->match_distance_m, $trip->departure_time->timestamp])
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

        return $trips;
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
}
