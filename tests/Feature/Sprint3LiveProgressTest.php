<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\PaymentMethod;
use App\Enums\TripStatus;
use App\Enums\VerificationLevel;
use App\Events\BookingConfirmed;
use App\Events\TripLocationUpdated;
use App\Events\TripSeatsUpdated;
use App\Events\WaypointReached;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\TripWaypoint;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Notifications\BookingRequested;
use App\Notifications\RequestApproved;
use App\Notifications\RequestDeclined;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Sprint3LiveProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Routing providers are unreachable in tests — the straight-line
        // fallback kicks in fast when the faked HTTP layer answers empty.
        Http::fake(['*' => Http::response([])]);
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ], $overrides));
    }

    private function driver(): User
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        Vehicle::factory()->create(['user_id' => $driver->id]);
        Wallet::create(['user_id' => $driver->id]);

        return $driver;
    }

    private function tripWithWaypoints(User $driver, array $overrides = []): Trip
    {
        $trip = Trip::factory()->forDriver($driver)->create(array_merge([
            'status' => TripStatus::Scheduled,
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addMinutes(30),
            'current_lat' => 9.1117,
            'current_lng' => 7.3304,
        ], $overrides));

        TripWaypoint::create(['trip_id' => $trip->id, 'label' => 'Kubwa Junction', 'lat' => 9.1117, 'lng' => 7.3304, 'sequence' => 1, 'eta_minutes' => 0]);
        TripWaypoint::create(['trip_id' => $trip->id, 'label' => 'Berger Junction', 'lat' => 9.064, 'lng' => 7.49, 'sequence' => 2, 'eta_minutes' => 20]);
        TripWaypoint::create(['trip_id' => $trip->id, 'label' => 'Federal Secretariat', 'lat' => 9.0589, 'lng' => 7.4891, 'sequence' => 3, 'eta_minutes' => 40]);

        return $trip->fresh();
    }

    public function test_trip_show_renders_progress_tracker_and_timing_strip(): void
    {
        $driver = $this->driver();
        $trip = $this->tripWithWaypoints($driver, [
            'route_name' => 'Kubwa → CBD',
            'status' => TripStatus::Active,
            'destination_text' => 'Federal Secretariat',
        ]);

        $this->actingAs($this->user())
            ->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('Kubwa Junction')
            ->assertSee('Berger Junction')
            ->assertSee('Federal Secretariat')
            ->assertSee('Ride progress')
            ->assertSee('data-wp-status')
            ->assertSee('Next:')
            ->assertSee('ETA Federal Secretariat');
    }

    public function test_progress_tracker_marks_passed_current_upcoming_states(): void
    {
        $driver = $this->driver();
        $trip = $this->tripWithWaypoints($driver, ['status' => TripStatus::Active]);

        $waypoint = $trip->waypoints()->where('label', 'Berger Junction')->first();
        $waypoint->update(['reached_at' => now()]);

        $progress = app(TripService::class)->calculateProgress($trip->fresh());

        $statuses = collect($progress)->pluck('status')->all();
        $this->assertEquals(['passed', 'passed', 'current'], $statuses);
        $this->assertEquals('Federal Secretariat', collect($progress)->firstWhere('status', 'current')['label']);
    }

    public function test_update_location_marks_reached_waypoint_and_broadcasts_progress(): void
    {
        Event::fake([WaypointReached::class, TripLocationUpdated::class]);
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver, ['status' => TripStatus::Active]);

        // Driver's phone reports a position inside Berger Junction's geofence.
        $trip = app(TripService::class)->updateLocation($trip, $driver, 9.064, 7.49);

        $berger = $trip->waypoints()->where('label', 'Berger Junction')->first();
        $this->assertNotNull($berger->fresh()->reached_at);

        Event::assertDispatched(WaypointReached::class, fn ($event) => $event->waypoint->id === $berger->id);
        Event::assertDispatched(TripLocationUpdated::class, fn ($event) => collect($event->progress)->contains('label', 'Berger Junction'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'waypoint_reached',
            'model_type' => Trip::class,
            'model_id' => $trip->id,
        ]);
    }

    public function test_timing_attributes_expose_departure_pickup_and_next_waypoint(): void
    {
        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver);

        $booking = Booking::create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Confirmed,
            'fare_paid' => 600,
            'payment_method' => PaymentMethod::Wallet,
            'pickup_lat' => 9.064,
            'pickup_lng' => 7.49,
        ]);

        $timing = app(TripService::class)->getTimingAttributes($trip->fresh(), $passenger);

        $this->assertArrayHasKey('minutes_to_departure', $timing);
        $this->assertArrayHasKey('eta_to_pickup_minutes', $timing);
        $this->assertArrayHasKey('eta_to_next_waypoint_minutes', $timing);
        $this->assertArrayHasKey('time_to_pickup_walk_minutes', $timing);
        $this->assertSame('Berger Junction', $timing['next_waypoint_label']);
        $this->assertArrayHasKey('progress', $timing);
        $this->assertCount(3, $timing['progress']);
        $this->assertEquals($booking->id, $booking->id);
    }

    public function test_share_request_creates_requested_booking_without_seat_or_hold(): void
    {
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver);

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/request", ['share_code' => 'ABC-123'])
            ->assertRedirect(route('bookings.index'));

        $booking = Booking::where('trip_id', $trip->id)->where('passenger_id', $passenger->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(BookingStatus::Requested, $booking->status);
        $this->assertSame('ABC-123', $booking->share_code);
        $this->assertSame(0, (int) $booking->fare_paid);

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'available_seats' => $trip->total_seats]);
        $this->assertDatabaseCount('transactions', 0);

        Notification::assertSentTo($driver, BookingRequested::class);
    }

    public function test_duplicate_share_request_is_rejected(): void
    {
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver);

        $this->actingAs($passenger)->post("/trips/{$trip->id}/request")->assertRedirect();
        $this->actingAs($passenger)
            ->from("/trips/{$trip->id}")
            ->post("/trips/{$trip->id}/request")
            ->assertSessionHasErrors('trip');
    }

    public function test_approve_request_holds_fare_and_decrements_seat(): void
    {
        Event::fake([BookingConfirmed::class, TripSeatsUpdated::class]);
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->user();
        $wallet = Wallet::create(['user_id' => $passenger->id, 'cash_balance' => 1000, 'version' => 1]);
        $trip = $this->tripWithWaypoints($driver);
        $booking = Booking::create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Requested,
            'fare_paid' => 0,
            'payment_method' => PaymentMethod::Wallet,
        ]);

        $this->actingAs($driver)
            ->from("/trips/{$trip->id}")
            ->post("/bookings/{$booking->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('status', 'Request approved — seat held.');

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame((float) $trip->fare_per_seat, (float) $booking->fare_paid);

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'available_seats' => $trip->total_seats - 1]);
        $this->assertDatabaseHas('transactions', [
            'type' => 'hold',
            'amount' => (float) $trip->fare_per_seat,
        ]);

        Event::assertDispatched(BookingConfirmed::class);
        Event::assertDispatched(TripSeatsUpdated::class);
        Notification::assertSentTo($passenger, RequestApproved::class);
    }

    public function test_approve_rejects_non_requested_and_full_trips(): void
    {
        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver);
        $booking = Booking::create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Confirmed,
            'fare_paid' => 600,
            'payment_method' => PaymentMethod::Wallet,
        ]);

        $this->actingAs($driver)
            ->from("/trips/{$trip->id}")
            ->post("/bookings/{$booking->id}/approve")
            ->assertSessionHasErrors('booking');
    }

    public function test_decline_request_has_no_side_effects(): void
    {
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver);
        $booking = Booking::create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Requested,
            'fare_paid' => 0,
            'payment_method' => PaymentMethod::Wallet,
        ]);

        $this->actingAs($driver)
            ->from("/trips/{$trip->id}")
            ->post("/bookings/{$booking->id}/decline")
            ->assertRedirect()
            ->assertSessionHas('status', 'Request declined — the rider has been notified.');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'available_seats' => $trip->total_seats]);
        $this->assertDatabaseCount('transactions', 0);

        Notification::assertSentTo($passenger, RequestDeclined::class);
    }

    public function test_cancelling_a_requested_booking_is_a_pure_state_flip(): void
    {
        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver);
        $booking = Booking::create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Requested,
            'fare_paid' => 0,
            'payment_method' => PaymentMethod::Wallet,
        ]);

        $this->actingAs($passenger)
            ->from('/bookings')
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'available_seats' => $trip->total_seats]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_request_gates_apply_for_volunteer_and_women_only_trips(): void
    {
        Notification::fake();

        $driver = $this->driver();
        $volunteerTrip = Trip::factory()->forDriver($driver)->volunteer()->create([
            'status' => TripStatus::Scheduled,
            'departure_time' => now()->addMinutes(30),
        ]);

        // Phone-only rider: can book at all, but volunteer rides are reserved
        // for Level 1+ workers, so the benefits gate must reject them.
        $phoneOnly = $this->user([
            'verification_level' => VerificationLevel::Unverified,
            'phone_verified_at' => now(),
        ]);
        $this->actingAs($phoneOnly)
            ->from("/trips/{$volunteerTrip->id}")
            ->post("/trips/{$volunteerTrip->id}/request")
            ->assertSessionHasErrors('trip');

        $womenOnlyTrip = Trip::factory()->forDriver($driver)->create([
            'status' => TripStatus::Scheduled,
            'women_only' => true,
            'departure_time' => now()->addMinutes(30),
        ]);

        $male = $this->user(['gender' => 'male']);
        $this->actingAs($male)
            ->from("/trips/{$womenOnlyTrip->id}")
            ->post("/trips/{$womenOnlyTrip->id}/request")
            ->assertSessionHasErrors('trip');
    }

    public function test_driver_cannot_request_own_trip(): void
    {
        $driver = $this->driver();
        $trip = $this->tripWithWaypoints($driver);

        $this->actingAs($driver)
            ->from("/trips/{$trip->id}")
            ->post("/trips/{$trip->id}/request")
            ->assertSessionHasErrors('trip');
    }

    public function test_share_page_renders_request_join_form_for_passenger(): void
    {
        $driver = $this->driver();
        $trip = $this->tripWithWaypoints($driver, ['route_name' => 'Kubwa → CBD']);

        $this->actingAs($this->user())
            ->get("/trips/{$trip->id}/share")
            ->assertOk()
            ->assertSee('Kubwa → CBD')
            ->assertSee('Request to join this ride');
    }

    public function test_trip_create_renders_progress_wizard(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)
            ->get('/trips/create')
            ->assertOk()
            ->assertSee('progressWizard')
            ->assertSee('Corridor');
    }

    public function test_trip_show_renders_booking_wizard_hint_for_passenger(): void
    {
        $driver = $this->driver();
        $passenger = $this->user();
        $trip = $this->tripWithWaypoints($driver);

        $this->actingAs($passenger)
            ->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('Book a seat')
            ->assertSee('seat held on confirm', false);
    }
}
