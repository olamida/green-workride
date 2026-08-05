<?php

namespace Tests\Feature;

use App\Enums\DemandRequestStatus;
use App\Enums\TripInterestStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Events\TripSeatsUpdated;
use App\Models\DemandRequest;
use App\Models\Trip;
use App\Models\TripInterest;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TripInterestTest extends TestCase
{
    use RefreshDatabase;

    private function driver(): User
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        Vehicle::factory()->create(['user_id' => $driver->id]);
        Wallet::create(['user_id' => $driver->id]);

        return $driver;
    }

    private function verifiedWorker(): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_passenger_can_register_interest_on_a_trip(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();
        $rider = $this->verifiedWorker();

        $this->actingAs($rider)
            ->post(route('trips.interest', $trip))
            ->assertRedirect();

        $this->assertDatabaseHas('trip_interests', [
            'trip_id' => $trip->id,
            'user_id' => $rider->id,
            'status' => TripInterestStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'available_seats' => $trip->total_seats,
        ]);
    }

    public function test_interest_is_idempotent_per_trip_and_user(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();
        $rider = $this->verifiedWorker();

        $this->actingAs($rider)->post(route('trips.interest', $trip));
        $this->actingAs($rider)->post(route('trips.interest', $trip));

        $this->assertSame(1, TripInterest::where('trip_id', $trip->id)->where('user_id', $rider->id)->count());
    }

    public function test_driver_cannot_register_interest_in_own_trip(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();

        $this->actingAs($driver)
            ->post(route('trips.interest', $trip))
            ->assertSessionHasErrors('trip');

        $this->assertDatabaseCount('trip_interests', 0);
    }

    public function test_completed_or_departed_trips_reject_interest(): void
    {
        $driver = $this->driver();
        $rider = $this->verifiedWorker();

        $completed = Trip::factory()->forDriver($driver)->status(TripStatus::Completed)->create();
        $this->actingAs($rider)->post(route('trips.interest', $completed))->assertSessionHasErrors('trip');

        $departed = Trip::factory()->forDriver($driver)->create(['departure_time' => now()->subHour()]);
        $this->actingAs($rider)->post(route('trips.interest', $departed))->assertSessionHasErrors('trip');

        $this->assertDatabaseCount('trip_interests', 0);
    }

    public function test_booking_upgrades_interest_to_matched(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create(['fare_per_seat' => 600]);
        $rider = $this->verifiedWorker();

        $this->actingAs($rider)->post(route('trips.interest', $trip));

        $this->actingAs($rider)
            ->post(route('bookings.store', $trip), ['payment_method' => 'cash']);

        $this->assertDatabaseHas('trip_interests', [
            'trip_id' => $trip->id,
            'user_id' => $rider->id,
            'status' => TripInterestStatus::Matched->value,
        ]);
    }

    public function test_cancelling_booking_reverts_interest_to_pending(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create(['fare_per_seat' => 600]);
        $rider = $this->verifiedWorker();

        $this->actingAs($rider)->post(route('trips.interest', $trip));

        $this->actingAs($rider)->post(route('bookings.store', $trip), ['payment_method' => 'cash']);

        $bookingRow = $trip->bookings()->where('passenger_id', $rider->id)->first();

        $this->assertDatabaseHas('trip_interests', ['status' => TripInterestStatus::Matched->value]);

        $this->actingAs($rider)
            ->post(route('bookings.cancel', $bookingRow));

        $this->assertDatabaseHas('trip_interests', [
            'trip_id' => $trip->id,
            'user_id' => $rider->id,
            'status' => TripInterestStatus::Pending->value,
        ]);
    }

    public function test_board_empty_state_uses_live_demand_signal(): void
    {
        $this->driver();

        DemandRequest::create([
            'user_id' => $this->verifiedWorker()->id,
            'pickup_lat' => 9.08,
            'pickup_lng' => 7.4,
            'destination_text' => 'Secretariat',
            'passengers_count' => 3,
            'requested_at' => now(),
            'status' => DemandRequestStatus::Pending,
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertSee('3 people want this journey', false)
            ->assertSee('Secretariat', false);
    }

    public function test_board_guides_with_next_departure(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'departure_time' => now()->addMinutes(25),
            'available_seats' => 3,
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertSee('Next departure', false)
            ->assertSee('3/4 seats left', false);
    }

    public function test_active_trips_sort_before_scheduled(): void
    {
        $driver = $this->driver();

        $scheduledSoon = Trip::factory()->forDriver($driver)->create([
            'departure_time' => now()->addMinutes(10),
        ]);
        $activeLater = Trip::factory()->forDriver($driver)->status(TripStatus::Active)->create([
            'departure_time' => now()->addMinutes(50),
        ]);

        $response = $this->actingAs($this->verifiedWorker())
            ->get('/trips?window=any');

        $html = $response->getContent();
        $this->assertTrue(strpos($html, 'data-trip-card="'.$activeLater->id.'"') < strpos($html, 'data-trip-card="'.$scheduledSoon->id.'"'));
    }

    public function test_leaving_soon_flag_badges_trips_departing_within_thirty_minutes(): void
    {
        $driver = $this->driver();
        $soon = Trip::factory()->forDriver($driver)->create([
            'departure_time' => now()->addMinutes(15),
        ]);
        $ahead = Trip::factory()->forDriver($driver)->create([
            'departure_time' => now()->addHours(6),
        ]);

        $response = $this->actingAs($this->verifiedWorker())
            ->get('/trips?window=any');

        $html = $response->getContent();
        $soonCard = substr($html, strpos($html, 'data-trip-card="'.$soon->id.'"'));
        $this->assertStringContainsString('Leaving soon', $soonCard);

        $aheadCard = substr($html, strpos($html, 'data-trip-card="'.$ahead->id.'"'));
        $this->assertStringContainsString('Book ahead', $aheadCard);
        $this->assertStringNotContainsString('Leaving soon', $aheadCard);
    }

    public function test_seat_updates_broadcast_on_public_trips_channel(): void
    {
        Event::fake([TripSeatsUpdated::class]);

        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create(['fare_per_seat' => 600]);
        $rider = $this->verifiedWorker();
        $rider->wallet->increment('cash_balance', 1000);

        $this->actingAs($rider)
            ->post(route('bookings.store', $trip), ['payment_method' => 'wallet']);

        Event::assertDispatched(TripSeatsUpdated::class, fn (TripSeatsUpdated $event) => $event->tripId === $trip->id && $event->availableSeats === $trip->total_seats - 1);
    }
}
