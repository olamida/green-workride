<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Events\WaypointReached;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\TripWaypoint;

/**
 * Live journey tracking service (Phase 1 Screen 5).
 *
 * Handles:
 * - Driver location updates via Reverb (15s cadence)
 * - Waypoint geofence arrival detection (100m default)
 * - JunctionArrived broadcast for Control Tower / ops
 * - ETA calculations for passenger-facing cards
 * - Payment capture + impact calculation on boarding
 * - SOS / Share ride audit trail
 */
class JourneyService
{
    public function __construct(
        private GeofenceService $geofence,
        private WalletService $wallet,
        private Co2Service $co2,
    ) {}

    /**
     * Process a driver location update (from TripService::updateLocation -> 15s cadence).
     *
     * @return array{arrived:bool, waypoint:TripWaypoint|null, etaMinutes:int|null}
     */
    public function processLocationUpdate(Trip $trip, float $lat, float $lng): array
    {
        $arrived = false;
        $waypoint = null;
        $etaMinutes = null;

        if ($trip->status !== TripStatus::Active) {
            return compact('arrived', 'waypoint', 'etaMinutes');
        }

        // Check if driver has reached the next boarding waypoint (100m geofence)
        $nextWaypoint = $this->getNextBoardingWaypoint($trip);
        if ($nextWaypoint) {
            $distance = $this->geofence->haversine(
                $lat, $lng,
                (float) $nextWaypoint->lat, (float) $nextWaypoint->lng
            );

            $radius = $nextWaypoint->geofence_radius_m ?? config('workride.waypoint.geofence_radius_m', 100);

            if ($distance <= $radius) {
                $arrived = true;
                $waypoint = $nextWaypoint;
                $this->markWaypointReached($trip, $waypoint);
            }
        }

        // Calculate ETA to final destination
        $etaMinutes = $this->calculateEtaToDestination($trip, $lat, $lng);

        return compact('arrived', 'waypoint', 'etaMinutes');
    }

    /**
     * Get the next waypoint that hasn't been reached yet and is a boarding point.
     */
    private function getNextBoardingWaypoint(Trip $trip): ?TripWaypoint
    {
        return $trip->waypoints()
            ->whereNull('reached_at')
            ->where('sequence', '>', 0) // Skip origin (sequence 0)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Mark a waypoint as reached and broadcast JunctionArrived.
     */
    private function markWaypointReached(Trip $trip, TripWaypoint $waypoint): void
    {
        $waypoint->update(['reached_at' => now()]);

        // Activity log for change control trail
        ActivityLog::log($trip->driver, 'waypoint_reached', Trip::class, $trip->id, [
            'waypoint_id' => $waypoint->id,
            'waypoint_label' => $waypoint->label,
            'waypoint_sequence' => $waypoint->sequence,
            'lat' => $waypoint->lat,
            'lng' => $waypoint->lng,
        ]);

        // Broadcast JunctionArrived on the private trip channel
        // This is picked up by the Connect Guide and Control Tower
        broadcast(new WaypointReached($trip, $waypoint))->toOthers();
    }

    /**
     * Calculate ETA from current driver position to final destination.
     *
     * Uses RoutingService when available, falls back to straight-line × route_factor.
     */
    private function calculateEtaToDestination(Trip $trip, float $lat, float $lng): ?int
    {
        if (! $trip->destination_text) {
            return null;
        }

        $destinationLat = $trip->destination_lat ?? 9.0589; // CBD fallback
        $destinationLng = $trip->destination_lng ?? 7.4891;

        $distanceKm = $this->geofence->haversine($lat, $lng, $destinationLat, $destinationLng);

        // Assume ~30 km/h average in city traffic (configurable)
        $avgSpeedKmh = config('workride.waypoint.avg_speed_kmh', 30);

        return max(1, (int) ceil(($distanceKm / $avgSpeedKmh) * 60));
    }

    /**
     * Get live journey data for the passenger's bottom card (Screen 5).
     */
    public function getLiveJourneyData(Booking $booking): array
    {
        $trip = $booking->trip;

        $etaMinutes = null;
        $driverDistanceM = null;
        $nextWaypointLabel = null;

        if ($trip->status === TripStatus::Active && $trip->current_lat && $trip->current_lng) {
            $etaMinutes = $this->calculateEtaToDestination(
                $trip,
                (float) $trip->current_lat,
                (float) $trip->current_lng
            );

            if ($booking->pickup_lat && $booking->pickup_lng) {
                $driverDistanceM = $this->geofence->haversine(
                    (float) $booking->pickup_lat,
                    (float) $booking->pickup_lng,
                    (float) $trip->current_lat,
                    (float) $trip->current_lng
                ) * 1000;
            }

            $nextWaypoint = $this->getNextBoardingWaypoint($trip);
            if ($nextWaypoint) {
                $nextWaypointLabel = $nextWaypoint->label;
            }
        }

        $progressSteps = [
            'booked' => [
                'label' => 'Booked',
                'completed' => true,
            ],
            'arriving' => [
                'label' => 'Arriving',
                'completed' => $booking->status === BookingStatus::Boarded
                    || $booking->status === BookingStatus::Completed,
            ],
            'boarded' => [
                'label' => 'Boarded',
                'completed' => $booking->status === BookingStatus::Boarded
                    || $booking->status === BookingStatus::Completed,
            ],
            'completed' => [
                'label' => 'Completed',
                'completed' => $booking->status === BookingStatus::Completed,
            ],
        ];

        return [
            'trip_id' => $trip->id,
            'route_name' => $trip->route_name,
            'status' => $trip->status->value,
            'booking_status' => $booking->status->value,
            'eta_minutes' => $etaMinutes,
            'driver_distance_m' => $driverDistanceM,
            'next_waypoint' => $nextWaypointLabel,
            'progress_steps' => $progressSteps,
            'driver' => $trip->driver ? [
                'name' => $trip->driver->name,
                'avatar' => $trip->driver->avatar,
                'verification_level' => $trip->driver->verification_level?->value,
            ] : null,
            'vehicle' => $trip->vehicle ? [
                'plate' => $trip->vehicle->plate_number,
                'make_model' => $trip->vehicle->make.' '.$trip->vehicle->model,
            ] : null,
        ];
    }

    /**
     * Capture payment and calculate impact when passenger boards.
     */
    public function onBoarded(Booking $booking): void
    {
        $trip = $booking->trip;

        // Capture the held fare (wallet hold -> actual charge)
        $this->wallet->captureForBooking($booking);

        // Calculate impact: CO2, fuel, trees saved
        $occupants = $trip->bookings()
            ->whereIn('status', [BookingStatus::Boarded, BookingStatus::Completed])
            ->count() + 1; // +1 for driver

        $distanceKm = $this->estimateTripDistance($trip);

        $impact = $this->co2->forRide($occupants, $distanceKm);

        // Store impact on the booking for the certificate
        $booking->update([
            'co2_saved_kg' => $impact['co2_kg'],
            'fuel_saved_litres' => $impact['fuel_litres'],
            'trees_equivalent' => $impact['trees'],
        ]);
    }

    /**
     * Estimate trip distance from waypoints or corridor fallback.
     */
    private function estimateTripDistance(Trip $trip): float
    {
        $waypoints = $trip->waypoints()->orderBy('sequence')->get();

        if ($waypoints->count() >= 2) {
            $distance = 0;
            for ($i = 1; $i < $waypoints->count(); $i++) {
                $distance += $this->geofence->haversine(
                    (float) $waypoints[$i - 1]->lat,
                    (float) $waypoints[$i - 1]->lng,
                    (float) $waypoints[$i]->lat,
                    (float) $waypoints[$i]->lng
                );
            }

            return $distance;
        }

        // Fallback to corridor distance
        return (float) config("workride.corridor_distance_km.{$trip->corridor?->value}", 20);
    }

    /**
     * Log SOS event (audit trail, offline queue via Background Sync).
     */
    public function logSos(Booking $booking, array $context = []): void
    {
        $trip = $booking->trip;

        ActivityLog::log($booking->passenger, 'sos', Booking::class, $booking->id, array_merge([
            'trip_id' => $trip->id,
            'corridor' => $trip->corridor?->value,
            'route' => $trip->route_name,
            'lat' => $trip->current_lat,
            'lng' => $trip->current_lng,
            'reported_at' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log share ride event (audit trail).
     */
    public function logShareRide(Booking $booking): void
    {
        ActivityLog::log($booking->passenger, 'share_ride', Booking::class, $booking->id, [
            'trip_id' => $booking->trip_id,
            'shared_at' => now()->toIso8601String(),
        ]);
    }
}
