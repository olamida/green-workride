<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
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

    private function passenger(float $cashBalance = 2000, float $subsidy = 0): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => $cashBalance,
            'subsidy_credits' => $subsidy,
        ]);

        return $user;
    }

    private function bookableTrip(?User $driver = null, float $fare = 600, int $seats = 4): Trip
    {
        $driver = $driver ?? $this->driver();

        return Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'fare_per_seat' => $fare,
            'total_seats' => $seats,
            'available_seats' => $seats,
            'departure_time' => now()->addMinutes(30),
        ]);
    }

    public function test_unverified_user_cannot_book(): void
    {
        $trip = $this->bookableTrip();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertForbidden();
    }

    public function test_api_unverified_user_cannot_book(): void
    {
        $trip = $this->bookableTrip();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/trips/{$trip->id}/bookings", ['payment_method' => 'wallet'])
            ->assertForbidden();

        $this->assertDatabaseMissing('bookings', ['passenger_id' => $user->id]);
    }

    public function test_api_unverified_user_cannot_list_bookings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertForbidden();
    }

    public function test_verified_worker_can_book_and_hold_is_applied(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $booking = $trip->bookings()->first();

        $this->assertNotNull($booking);
        $this->assertEquals(BookingStatus::Confirmed, $booking->status);
        $this->assertEquals(3, $trip->fresh()->available_seats);
        $this->assertEquals(1400, (float) $passenger->fresh()->wallet->cash_balance);

        $this->assertDatabaseHas('transactions', [
            'reference' => "BOOK-{$booking->id}-HOLD",
            'type' => 'hold',
            'amount' => 600,
        ]);
    }

    public function test_passenger_cannot_book_own_trip(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create(['departure_time' => now()->addMinutes(30)]);

        $this->actingAs($driver)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('trip');
    }

    public function test_cannot_book_full_trip(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();
        $trip->update(['available_seats' => 0]);

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('trip');
    }

    public function test_cannot_book_same_trip_twice(): void
    {
        $passenger = $this->passenger(5000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();
        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('trip');

        $this->assertEquals(1, $trip->bookings()->count());
    }

    public function test_subsidy_credits_are_spent_first(): void
    {
        $passenger = $this->passenger(cashBalance: 500, subsidy: 600);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertRedirect();

        $wallet = $passenger->fresh()->wallet;
        $this->assertEquals(0, (float) $wallet->subsidy_credits);
        $this->assertEquals(500, (float) $wallet->cash_balance);
    }

    public function test_booking_fails_with_insufficient_wallet_balance(): void
    {
        $passenger = $this->passenger(100);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseMissing('bookings', ['passenger_id' => $passenger->id]);
        $this->assertEquals(4, $trip->fresh()->available_seats);
    }

    public function test_free_volunteer_trip_books_without_payment(): void
    {
        $passenger = $this->passenger(100);
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->volunteer()->create([
            'departure_time' => now()->addMinutes(30),
        ]);

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'payment_method' => 'free',
            'fare_paid' => 0,
        ]);

        $this->assertEquals(100, (float) $passenger->fresh()->wallet->cash_balance);
    }

    public function test_cancel_booking_refunds_hold(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();

        $booking = $trip->bookings()->first();

        $this->actingAs($passenger)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertEquals(2000, (float) $passenger->fresh()->wallet->cash_balance);
        $this->assertEquals(4, $trip->fresh()->available_seats);
        $this->assertDatabaseHas('transactions', ['reference' => "BOOK-{$booking->id}-HOLD", 'type' => 'refund']);
    }

    public function test_board_captures_fare(): void
    {
        $driver = $this->driver();
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip($driver);

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();
        $booking = $trip->bookings()->first();

        $this->actingAs($driver)
            ->post("/bookings/{$booking->id}/board")
            ->assertRedirect();

        $this->assertEquals(BookingStatus::Boarded, $booking->fresh()->status);
        $this->assertEquals(1400, (float) $passenger->fresh()->wallet->cash_balance);
        $this->assertDatabaseHas('transactions', ['reference' => "BOOK-{$booking->id}-HOLD", 'type' => 'capture']);
    }

    public function test_no_show_captures_half_and_refunds_half(): void
    {
        $driver = $this->driver();
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip($driver);

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();
        $booking = $trip->bookings()->first();

        $this->actingAs($driver)
            ->post("/bookings/{$booking->id}/no-show")
            ->assertRedirect();

        $this->assertEquals(BookingStatus::NoShow, $booking->fresh()->status);
        $this->assertEquals(1700, (float) $passenger->fresh()->wallet->cash_balance);
        $this->assertDatabaseHas('transactions', [
            'reference' => "BOOK-{$booking->id}-HOLD",
            'type' => 'capture',
            'amount' => 300,
        ]);
    }

    public function test_complete_trip_settles_confirmed_bookings(): void
    {
        $driver = $this->driver();
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip($driver);

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();
        $booking = $trip->bookings()->first();

        $this->actingAs($driver)->post("/trips/{$trip->id}/start")->assertRedirect();
        $this->actingAs($driver)->post("/trips/{$trip->id}/complete")->assertRedirect();

        $this->assertEquals(TripStatus::Completed, $trip->fresh()->status);
        $this->assertEquals(BookingStatus::Completed, $booking->fresh()->status);
        $this->assertDatabaseHas('transactions', ['reference' => "BOOK-{$booking->id}-HOLD", 'type' => 'capture']);
    }

    public function test_cash_booking_logs_driver_collection_on_board(): void
    {
        $driver = $this->driver();
        $passenger = $this->passenger(0);
        $trip = $this->bookableTrip($driver);

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'cash'])->assertRedirect();
        $booking = $trip->bookings()->first();

        $this->assertEquals(0, (float) $passenger->fresh()->wallet->cash_balance);

        $this->actingAs($driver)
            ->post("/bookings/{$booking->id}/board")
            ->assertRedirect();

        $this->assertEquals(600, (float) $driver->fresh()->wallet->cash_collected_log);
        $this->assertDatabaseHas('transactions', ['reference' => "BOOK-{$booking->id}-CASH", 'type' => 'capture']);
    }

    public function test_api_can_book_and_list_bookings(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/trips/{$trip->id}/bookings", ['payment_method' => 'wallet'])
            ->assertCreated()
            ->assertJsonPath('booking.status', 'confirmed');

        $this->actingAs($passenger, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.trip.id', $trip->id);
    }

    public function test_guest_cannot_access_my_rides(): void
    {
        $this->get('/bookings')->assertRedirect('/login');
    }
}
