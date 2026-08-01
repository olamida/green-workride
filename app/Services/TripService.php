<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Events\TripCancelled;
use App\Events\TripCompleted;
use App\Events\TripLocationUpdated;
use App\Events\TripPublished;
use App\Events\TripStarted;
use App\Jobs\CalculateImpactJob;
use App\Jobs\GenerateGtfsFeedJob;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

class TripService
{
    public function __construct(
        private PricingService $pricing,
        private GeofenceService $geofence,
        private BookingService $bookings,
        private RideCreditService $rideCredits,
    ) {}

    public function publish(User $driver, array $data): Trip
    {
        $isFreeVolunteer = (bool) ($data['is_free_volunteer'] ?? false);
        $corridor = Corridor::from($data['corridor']);
        $totalSeats = (int) $data['total_seats'];

        $vehicle = $this->resolveVehicle($driver, $data, $isFreeVolunteer);

        $lat = isset($data['current_lat']) && $data['current_lat'] !== '' ? (float) $data['current_lat'] : null;
        $lng = isset($data['current_lng']) && $data['current_lng'] !== '' ? (float) $data['current_lng'] : null;

        if (($lat !== null || $lng !== null) && ($lat === null || $lng === null || ! $this->geofence->isInsideFct($lat, $lng))) {
            throw ValidationException::withMessages(['current_lat' => 'Trip must be published from within the FCT.']);
        }

        $trip = $driver->trips()->create([
            'vehicle_id' => $vehicle?->id,
            'route_name' => $corridor->label(),
            'corridor' => $corridor,
            'origin_text' => $data['origin_text'],
            'destination_text' => $data['destination_text'],
            'current_lat' => $lat,
            'current_lng' => $lng,
            'total_seats' => $totalSeats,
            'available_seats' => $totalSeats,
            'fare_per_seat' => $this->pricing->fareFor($corridor, $isFreeVolunteer),
            'is_free_volunteer' => $isFreeVolunteer,
            'status' => TripStatus::Scheduled,
            'departure_time' => $data['departure_time'],
            'waypoints' => $data['waypoints'] ?? null,
        ]);

        foreach ($data['waypoints'] ?? [] as $index => $waypoint) {
            $trip->waypoints()->create([
                'label' => $waypoint['label'],
                'lat' => $waypoint['lat'],
                'lng' => $waypoint['lng'],
                'sequence' => $index + 1,
            ]);
        }

        event(new TripPublished($trip));

        $this->queueGtfsRegeneration();

        return $trip->load('driver', 'vehicle', 'waypoints');
    }

    public function start(Trip $trip, User $driver, ?float $lat = null, ?float $lng = null): Trip
    {
        $this->assertDriver($trip, $driver);

        if ($trip->status !== TripStatus::Scheduled) {
            throw ValidationException::withMessages(['trip' => 'Only scheduled trips can be started.']);
        }

        $trip->update([
            'status' => TripStatus::Active,
            'current_lat' => $lat ?? $trip->current_lat,
            'current_lng' => $lng ?? $trip->current_lng,
        ]);

        event(new TripStarted($trip->fresh()));

        return $trip->fresh();
    }

    public function updateLocation(Trip $trip, User $driver, float $lat, float $lng): Trip
    {
        $this->assertDriver($trip, $driver);

        if (in_array($trip->status, [TripStatus::Completed, TripStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['trip' => 'Location can only be updated on a live trip.']);
        }

        $trip->update(['current_lat' => $lat, 'current_lng' => $lng]);

        broadcast(new TripLocationUpdated($trip->fresh()));

        return $trip->fresh();
    }

    public function completeTrip(Trip $trip, User $driver): Trip
    {
        $this->assertDriver($trip, $driver);

        if ($trip->status !== TripStatus::Active) {
            throw ValidationException::withMessages(['trip' => 'Only active trips can be completed.']);
        }

        $trip->update(['status' => TripStatus::Completed]);

        foreach ($trip->bookings as $booking) {
            if (in_array($booking->status->value, ['confirmed', 'boarded'], true)) {
                $this->bookings->settle($booking);
                $booking->update(['status' => BookingStatus::Completed]);
                // Time-Bank: every passenger carried repays one seat of the
                // driver's oldest open ride credit ("Ride Now, Drive Later").
                $this->rideCredits->repayWithDrive($driver, $booking);
            }
        }

        event(new TripCompleted($trip->fresh()));

        dispatch(new CalculateImpactJob($trip->id));

        return $trip->fresh();
    }

    public function cancelTrip(Trip $trip, User $actor, ?string $reason = null): Trip
    {
        if ($trip->driver_id !== $actor->id && ! $actor->isAdmin()) {
            throw ValidationException::withMessages(['trip' => 'Only the driver or an admin can cancel this trip.']);
        }

        if (in_array($trip->status, [TripStatus::Completed, TripStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['trip' => 'Trip already closed.']);
        }

        $trip->update(['status' => TripStatus::Cancelled]);

        foreach ($trip->bookings as $booking) {
            if (in_array($booking->status->value, ['confirmed', 'boarded'], true)) {
                $this->bookings->cancelBooking($booking, $actor, 'Trip cancelled by driver.');
            }
        }

        $trip->update(['available_seats' => $trip->total_seats]);

        event(new TripCancelled($trip->fresh(), $reason));

        return $trip->fresh();
    }

    private function resolveVehicle(User $driver, array $data, bool $isFreeVolunteer): ?Vehicle
    {
        if (! empty($data['vehicle_id'])) {
            $vehicle = Vehicle::find($data['vehicle_id']);

            if (! $vehicle || $vehicle->user_id !== $driver->id) {
                throw ValidationException::withMessages(['vehicle_id' => 'Vehicle not found for this driver.']);
            }

            if (! $isFreeVolunteer && ! $vehicle->papers_verified) {
                throw ValidationException::withMessages(['vehicle_id' => 'Vehicle papers must be verified to publish paid rides.']);
            }

            return $vehicle;
        }

        if ($isFreeVolunteer) {
            return null;
        }

        $vehicle = $driver->vehicles()->where('papers_verified', true)->first();

        if (! $vehicle) {
            throw ValidationException::withMessages(['vehicle_id' => 'Add a verified vehicle before publishing paid rides.']);
        }

        return $vehicle;
    }

    private function assertDriver(Trip $trip, User $user): void
    {
        if ($trip->driver_id !== $user->id) {
            throw ValidationException::withMessages(['trip' => 'Only the trip driver can perform this action.']);
        }
    }

    /**
     * Kick off a queued GTFS feed regeneration so Google's feed reflects the
     * new trip as soon as possible instead of waiting for the nightly job.
     */
    private function queueGtfsRegeneration(): void
    {
        GenerateGtfsFeedJob::dispatch();
    }
}
