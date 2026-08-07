<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\MissionActivityType;
use App\Enums\RewardTrigger;
use App\Enums\TripInterestStatus;
use App\Enums\TripStatus;
use App\Events\TripCancelled;
use App\Events\TripCompleted;
use App\Events\TripLocationUpdated;
use App\Events\TripPublished;
use App\Events\TripStarted;
use App\Events\UserArrivedAtPickup;
use App\Events\WaypointReached;
use App\Jobs\CalculateImpactJob;
use App\Jobs\GenerateGtfsFeedJob;
use App\Models\ActivityLog;
use App\Models\Trip;
use App\Models\TripInterest;
use App\Models\TripWaypoint;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\UserArrivedAtPickupNotification;
use App\Notifications\WaypointReachedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class TripService
{
    public function __construct(
        private PricingService $pricing,
        private GeofenceService $geofence,
        private BookingService $bookings,
        private RideCreditService $rideCredits,
        private RewardService $rewards,
        private MissionService $missions,
        private FleetService $fleet,
        private StakeholderService $stakeholders,
        private RoutingService $routing,
        private NotificationService $notifications,
    ) {}

    /**
     * @param  int|null  $repeatHorizonDays  Cap the repeat companion window.
     *                                       Defaults to `scheduling.repeat_horizon_days`.
     */
    public function publish(User $driver, array $data, ?int $repeatHorizonDays = null): Trip
    {
        if (! $driver->canBookBenefits()) {
            throw ValidationException::withMessages(['trip' => 'Publishing trips requires formal verification (Level 1+).']);
        }

        $isFreeVolunteer = (bool) ($data['is_free_volunteer'] ?? false);
        $womenOnly = (bool) ($data['women_only'] ?? false);
        $corridor = Corridor::from($data['corridor']);
        $totalSeats = (int) $data['total_seats'];

        $vehicle = $this->resolveVehicle($driver, $data, $isFreeVolunteer);

        $asset = $this->fleet->assertPublishable($driver, isset($data['asset_id']) ? (int) $data['asset_id'] : null);

        $lat = isset($data['current_lat']) && $data['current_lat'] !== '' ? (float) $data['current_lat'] : null;
        $lng = isset($data['current_lng']) && $data['current_lng'] !== '' ? (float) $data['current_lng'] : null;

        if (($lat !== null || $lng !== null) && ($lat === null || $lng === null || ! $this->geofence->isInsideFct($lat, $lng))) {
            throw ValidationException::withMessages(['current_lat' => 'Trip must be published from within the FCT.']);
        }

        $trip = $driver->trips()->create([
            'vehicle_id' => $vehicle?->id,
            'asset_id' => $asset?->id,
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
            'women_only' => $womenOnly,
            'status' => TripStatus::Scheduled,
            'departure_time' => $data['departure_time'],
            'waypoints' => $data['waypoints'] ?? null,
            'repeat_group' => $this->repeatGroupFor($driver, $data),
        ]);

        foreach ($data['waypoints'] ?? [] as $index => $waypoint) {
            $trip->waypoints()->create([
                'label' => $waypoint['label'],
                'lat' => $waypoint['lat'],
                'lng' => $waypoint['lng'],
                'sequence' => $index + 1,
            ]);
        }

        $this->publishRepeatCompanions($trip, $data, $corridor, $isFreeVolunteer, $womenOnly, $totalSeats, $repeatHorizonDays);

        event(new TripPublished($trip));

        $this->queueGtfsRegeneration();

        return $trip->load('driver', 'vehicle', 'waypoints');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function repeatGroupFor(User $driver, array $data): ?string
    {
        if (! ($data['repeat'] ?? false) || empty($data['repeat_days'])) {
            return null;
        }

        return sprintf('REP-%d-%s-%s', $driver->id, now()->format('YmdHis'), substr((string) uniqid('', true), -6));
    }

    /**
     * "Repeat Mon–Fri" carpool publishing: on the same weekday, at the same
     * time-of-day, for the configured horizon, create companion Trip rows in
     * the same repeat_group. Re-submitting the form is a no-op — a companion
     * whose (repeat_group, departure_time) already exists is skipped.
     */
    private function publishRepeatCompanions(
        Trip $trip,
        array $data,
        Corridor $corridor,
        bool $isFreeVolunteer,
        bool $womenOnly,
        int $totalSeats,
        ?int $horizonDays = null,
    ): void {
        $repeatGroup = $trip->repeat_group;

        if ($repeatGroup === null) {
            return;
        }

        $days = collect($data['repeat_days'])->map(fn ($day) => strtolower((string) $day))->all();
        $horizonDays = $horizonDays ?? (int) config('workride.scheduling.repeat_horizon_days', 14);
        $time = $trip->departure_time?->format('H:i') ?? '07:00';

        $waypoints = $trip->waypoints()->orderBy('sequence')->get()->map(fn ($wp) => [
            'label' => $wp->label,
            'lat' => (float) $wp->lat,
            'lng' => (float) $wp->lng,
        ])->all();

        for ($offset = 1; $offset <= $horizonDays; $offset++) {
            $departure = $trip->departure_time?->copy()->addDays($offset);
            $weekday = strtolower($departure?->format('D') ?? '');

            if (! in_array($weekday, $days, true)) {
                continue;
            }

            $departureAt = ($departure?->format('Y-m-d') ?? '') === '' ? null : $departure;
            $key = $trip->departure_time->format('H:i');

            $exists = Trip::query()
                ->where('repeat_group', $repeatGroup)
                ->whereDate('departure_time', $departure->toDateString())
                ->whereTime('departure_time', $key)
                ->exists();

            if ($exists || $departureAt === null) {
                continue;
            }

            $companion = Trip::create([
                'driver_id' => $trip->driver_id,
                'vehicle_id' => $trip->vehicle_id,
                'asset_id' => $trip->asset_id,
                'route_name' => $corridor->label(),
                'corridor' => $corridor,
                'origin_text' => $trip->origin_text,
                'destination_text' => $trip->destination_text,
                'total_seats' => $totalSeats,
                'available_seats' => $totalSeats,
                'fare_per_seat' => $this->pricing->fareFor($corridor, $isFreeVolunteer),
                'is_free_volunteer' => $isFreeVolunteer,
                'women_only' => $womenOnly,
                'status' => TripStatus::Scheduled,
                'departure_time' => $departureAt,
                'waypoints' => $waypoints,
                'repeat_group' => $repeatGroup,
            ]);

            foreach ($waypoints as $index => $waypoint) {
                $companion->waypoints()->create([
                    'label' => $waypoint['label'],
                    'lat' => $waypoint['lat'],
                    'lng' => $waypoint['lng'],
                    'sequence' => $index + 1,
                ]);
            }

            event(new TripPublished($companion));
        }
    }

    /**
     * Passenger interest registration ("I want this journey", section 2.2).
     * A soft signal that never touches seats or money; upgrades to a real
     * booking (matched) when the passenger books. Idempotent per (trip, user).
     */
    public function registerInterest(Trip $trip, User $passenger): TripInterest
    {
        if ($trip->driver_id === $passenger->id) {
            throw ValidationException::withMessages(['trip' => 'You cannot register interest in your own trip.']);
        }

        if (in_array($trip->status, [TripStatus::Completed, TripStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['trip' => 'This trip is no longer accepting interest.']);
        }

        if ($trip->departure_time?->isPast()) {
            throw ValidationException::withMessages(['trip' => 'This trip has already departed.']);
        }

        return TripInterest::updateOrCreate(
            ['trip_id' => $trip->id, 'user_id' => $passenger->id],
            ['status' => TripInterestStatus::Pending, 'registered_at' => now()],
        );
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

        $trip->refresh();

        $this->markReachedWaypoints($trip);

        $this->notifyArrivingPassengers($trip);

        broadcast(new TripLocationUpdated($trip, $this->calculateProgress($trip)));

        return $trip;
    }

    /**
     * Passenger "500m away" nudges (guide §6 Workflow 1, roadmap P3.2).
     *
     * Called on every live location update while the trip is active. For each
     * confirmed/boarded passenger who has not already been nudged, and whose
     * pickup point is within workride.push.arrived_radius_m of the driver, we
     * stamp arrival_notified_at (idempotent), fire the UserArrivedAtPickup
     * broadcast, and send the arrival notification (database + log, plus FCM
     * push when configured) through NotificationService.
     */
    public function notifyArrivingPassengers(Trip $trip): void
    {
        if ($trip->status !== TripStatus::Active) {
            return;
        }

        $currentLat = $trip->current_lat;
        $currentLng = $trip->current_lng;

        if ($currentLat === null || $currentLng === null) {
            return;
        }

        $radiusM = (float) config('workride.push.arrived_radius_m', 500);

        $bookings = $trip->bookings()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Boarded])
            ->whereNull('arrival_notified_at')
            ->whereNotNull('pickup_lat')
            ->whereNotNull('pickup_lng')
            ->get();

        foreach ($bookings as $booking) {
            $distanceM = $this->geofence->haversine(
                (float) $currentLat,
                (float) $currentLng,
                (float) $booking->pickup_lat,
                (float) $booking->pickup_lng,
            );

            if ($distanceM > $radiusM) {
                continue;
            }

            $booking->update(['arrival_notified_at' => now()]);

            event(new UserArrivedAtPickup($trip->fresh(), $booking->fresh(), $distanceM));

            $this->notifications->send(
                $booking->passenger,
                new UserArrivedAtPickupNotification($trip->fresh(), $booking->fresh(), $distanceM),
            );
        }
    }

    /**
     * Live junction progress for the shared tracker (spec §3.1). Pure read —
     * never mutates. Reach detection/persistence lives in updateLocation().
     *
     * Each waypoint is resolved to passed / current / upcoming:
     *   - passed: reached_at stamped (already crossed).
     *   - current: the first waypoint not yet reached (the next stop).
     *   - upcoming: everything after the current one.
     *
     * Distance from origin uses the stored backfill when present, else a
     * Haversine from the first waypoint. ETA mirrors the stored eta_minutes,
     * else distance ÷ configured cruising speed.
     *
     * @return list<array<string, mixed>>
     */
    public function calculateProgress(Trip $trip): array
    {
        $waypoints = $trip->waypoints()->orderBy('sequence')->get();

        if ($waypoints->isEmpty()) {
            return [];
        }

        $currentLat = $trip->current_lat !== null ? (float) $trip->current_lat : null;
        $currentLng = $trip->current_lng !== null ? (float) $trip->current_lng : null;

        $origin = $waypoints->first();
        $originLat = (float) $origin->lat;
        $originLng = (float) $origin->lng;

        $currentAssigned = false;
        $progress = [];

        foreach ($waypoints as $waypoint) {
            $reached = $waypoint->reached_at;

            $within = false;
            if ($currentLat !== null && $currentLng !== null) {
                $distanceM = $this->geofence->haversine(
                    $currentLat,
                    $currentLng,
                    (float) $waypoint->lat,
                    (float) $waypoint->lng,
                );
                $within = $distanceM <= (float) ($waypoint->geofence_radius_m ?? config('workride.waypoint.geofence_radius_m', 100));
            }

            if ($reached || $within) {
                $status = 'passed';
            } elseif (! $currentAssigned) {
                $status = 'current';
                $currentAssigned = true;
            } else {
                $status = 'upcoming';
            }

            $distanceFromOrigin = $waypoint->distance_from_origin_km !== null
                ? (float) $waypoint->distance_from_origin_km
                : round($this->geofence->haversine($originLat, $originLng, (float) $waypoint->lat, (float) $waypoint->lng) / 1000, 2);

            $progress[] = [
                'id' => $waypoint->id,
                'label' => $waypoint->label,
                'lat' => (float) $waypoint->lat,
                'lng' => (float) $waypoint->lng,
                'sequence' => $waypoint->sequence,
                'is_major_hub' => (bool) $waypoint->is_major_hub,
                'eta' => $this->etaMinutesFromOrigin($waypoint, $distanceFromOrigin),
                'eta_minutes' => $this->etaMinutesFromOrigin($waypoint, $distanceFromOrigin),
                'distance' => $distanceFromOrigin,
                'distance_from_origin_km' => $distanceFromOrigin,
                'status' => $status,
                'reached_at' => $reached?->toIso8601String(),
                'within_geofence' => $within,
            ];
        }

        return $progress;
    }

    /**
     * Crossed-waypoint detection (spec §3.1 acceptance: fires when the vehicle
     * passes within the arrival geofence while the trip is active). Persists
     * reached_at, writes the change-control trail, broadcasts WaypointReached
     * to the private channel and notifies participants. Idempotent per
     * waypoint — already-reached stops are skipped.
     */
    public function markReachedWaypoints(Trip $trip): void
    {
        if ($trip->status !== TripStatus::Active) {
            return;
        }

        $lat = $trip->current_lat !== null ? (float) $trip->current_lat : null;
        $lng = $trip->current_lng !== null ? (float) $trip->current_lng : null;

        if ($lat === null || $lng === null) {
            return;
        }

        $waypoints = $trip->waypoints()->whereNull('reached_at')->orderBy('sequence')->get();

        foreach ($waypoints as $waypoint) {
            $distanceM = $this->geofence->haversine(
                $lat,
                $lng,
                (float) $waypoint->lat,
                (float) $waypoint->lng,
            );

            $radius = (float) ($waypoint->geofence_radius_m ?? config('workride.waypoint.geofence_radius_m', 100));

            if ($distanceM > $radius) {
                continue;
            }

            $waypoint->update(['reached_at' => now()]);

            ActivityLog::log($trip->driver, 'waypoint_reached', Trip::class, $trip->id, [
                'waypoint_id' => $waypoint->id,
                'label' => $waypoint->label,
                'sequence' => $waypoint->sequence,
            ]);

            broadcast(new WaypointReached($trip->fresh(), $waypoint->fresh()));

            $participants = $trip->bookings()
                ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Boarded, BookingStatus::Completed])
                ->get()
                ->pluck('passenger')
                ->push($trip->driver)
                ->unique('id');

            Notification::send($participants, new WaypointReachedNotification($trip->fresh(), $waypoint->fresh()));
        }
    }

    /**
     * Timing indicators (spec §3.2): countdown to departure, ETA to the
     * passenger's pickup, destination, next waypoint, delay vs schedule and
     * the walking time to the pickup. Every routing estimate degrades to a
     * free straight-line fallback so the UI never 500s on provider outages.
     *
     * @return array<string, mixed>
     */
    public function getTimingAttributes(Trip $trip, ?User $userForPickup = null): array
    {
        $current = $trip->current_lat !== null && $trip->current_lng !== null
            ? ['lat' => (float) $trip->current_lat, 'lng' => (float) $trip->current_lng]
            : null;

        $pickup = null;
        if ($userForPickup) {
            $booking = $trip->bookings()->where('passenger_id', $userForPickup->id)->first();
            if ($booking?->pickup_lat !== null && $booking?->pickup_lng !== null) {
                $pickup = ['lat' => (float) $booking->pickup_lat, 'lng' => (float) $booking->pickup_lng];
            }
        }

        $progress = $this->calculateProgress($trip);
        $currentWaypoint = collect($progress)->firstWhere('status', 'current');
        $nextWaypoint = $currentWaypoint ? ['lat' => (float) $currentWaypoint['lat'], 'lng' => (float) $currentWaypoint['lng']] : null;

        $destination = null;
        $last = $trip->waypoints()->orderByDesc('sequence')->first();
        if ($last?->lat !== null && $last?->lng !== null) {
            $destination = ['lat' => (float) $last->lat, 'lng' => (float) $last->lng];
        }

        $departure = $trip->departure_time;

        return [
            'minutes_to_departure' => $departure ? now()->diffInMinutes($departure, false) : null,
            'eta_to_pickup_minutes' => $current && $pickup ? $this->etaMinutes($current, $pickup) : null,
            'eta_to_destination_minutes' => $current && $destination ? $this->etaMinutes($current, $destination) : null,
            'eta_to_next_waypoint_minutes' => $current && $nextWaypoint ? $this->etaMinutes($current, $nextWaypoint) : null,
            'delay_minutes' => $trip->status === TripStatus::Active && $departure
                ? max(0, now()->diffInMinutes($departure, false))
                : 0,
            'time_to_pickup_walk_minutes' => $pickup && ($current ?? $nextWaypoint)
                ? $this->walkMinutes($pickup, $current ?? $nextWaypoint)
                : null,
            'next_waypoint_label' => $currentWaypoint['label'] ?? null,
            'progress' => $progress,
        ];
    }

    /**
     * ETA from origin (minutes) for a waypoint — stored stamp first, else
     * distance ÷ configured cruising speed.
     */
    private function etaMinutesFromOrigin(TripWaypoint $waypoint, float $distanceKm): ?int
    {
        if ($waypoint->eta_minutes !== null) {
            return $waypoint->eta_minutes;
        }

        $speed = (float) config('workride.waypoint.avg_speed_kmh', 30);

        return $speed > 0 ? (int) round($distanceKm / $speed * 60) : null;
    }

    /**
     * Driving ETA (minutes) between two points, with a free straight-line
     * fallback when the routing provider is unreachable.
     *
     * @param  array{lat:float,lng:float}  $from
     * @param  array{lat:float,lng:float}  $to
     */
    private function etaMinutes(array $from, array $to): ?int
    {
        try {
            $route = $this->routing->route($from, $to, 'driving');

            return (int) round($route['duration_s'] / 60);
        } catch (Throwable) {
            $distanceM = $this->geofence->haversine(
                (float) $from['lat'],
                (float) $from['lng'],
                (float) $to['lat'],
                (float) $to['lng'],
            );

            return (int) round($distanceM / ((float) config('workride.waypoint.avg_speed_kmh', 30) / 3.6) / 60);
        }
    }

    /**
     * Walking ETA (minutes) between two points using the guide's route factor
     * and walking speed — same fallback math as the connect guide.
     *
     * @param  array{lat:float,lng:float}  $from
     * @param  array{lat:float,lng:float}  $to
     */
    private function walkMinutes(array $from, array $to): ?int
    {
        $distanceM = $this->geofence->haversine(
            (float) $from['lat'],
            (float) $from['lng'],
            (float) $to['lat'],
            (float) $to['lng'],
        ) * (float) config('workride.guide.route_factor', 1.25);

        return (int) round($distanceM / ((float) config('workride.guide.walking_speed_kmh', 5) / 3.6) / 60);
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

        $this->stakeholders->recordForTrip($trip->fresh());
        $this->awardCompletionRewards($trip);
        $this->recordMissionProgress($trip);

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
     * Reward engine wiring (guide §2.2 stream #7): completed-trip triggers for
     * the driver and every carried passenger, the weekly/monthly streak
     * triggers, and the core green-points economy for volunteer rides.
     */
    private function awardCompletionRewards(Trip $trip): void
    {
        $context = ['event_key' => "trip-{$trip->id}", 'trip_id' => $trip->id];

        if ($trip->is_free_volunteer) {
            $this->rewards->creditGreenPoints($trip->driver, (int) config('workride.rewards.volunteer_green_points', 10));
            $this->rewards->award($trip->driver, RewardTrigger::VolunteerRide, $context);
        }

        $this->rewards->award($trip->driver, RewardTrigger::TripCompleted, $context);
        $this->rewards->award($trip->driver, RewardTrigger::WeeklyFiveRides, $context);
        $this->rewards->award($trip->driver, RewardTrigger::MonthlyTenRides, $context);

        foreach ($trip->bookings as $booking) {
            if (in_array($booking->status->value, ['boarded', 'completed'], true)) {
                $this->rewards->award($booking->passenger, RewardTrigger::TripCompleted, $context);
                $this->rewards->award($booking->passenger, RewardTrigger::WeeklyFiveRides, $context);
                $this->rewards->award($booking->passenger, RewardTrigger::MonthlyTenRides, $context);
            }
        }
    }

    /**
     * Missions observation (guide §9B demand + §8 stakeholder): every completed
     * ride counts toward the promoted activities it matches — volunteer/paid
     * for the driver, passenger/peak for everyone carried. MissionService
     * no-ops when the feature is off.
     */
    private function recordMissionProgress(Trip $trip): void
    {
        $context = ['trip_id' => $trip->id, 'corridor' => $trip->corridor->value];

        if ($trip->is_free_volunteer) {
            $this->missions->record($trip->driver, MissionActivityType::VolunteerRides, $context);
        } else {
            $this->missions->record($trip->driver, MissionActivityType::PaidRides, $context);
        }

        if ($this->isPeakHour($trip)) {
            $this->missions->record($trip->driver, MissionActivityType::PeakHourRides, $context);
        }

        foreach ($trip->bookings as $booking) {
            if (in_array($booking->status->value, ['boarded', 'completed'], true)) {
                $this->missions->record($booking->passenger, MissionActivityType::PassengerRides, $context);

                if ($this->isPeakHour($trip)) {
                    $this->missions->record($booking->passenger, MissionActivityType::PeakHourRides, $context);
                }
            }
        }
    }

    private function isPeakHour(Trip $trip): bool
    {
        $hour = (int) $trip->departure_time?->hour;

        return $hour >= 6 && $hour < 9 || $hour >= 16 && $hour < 19;
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
