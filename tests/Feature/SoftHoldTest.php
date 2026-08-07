<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Jobs\ReleaseExpiredSoftHoldsJob;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftHoldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['workride.soft_hold.enabled' => true]);
        config(['workride.soft_hold.ttl_minutes' => 3]);
    }

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

    private function passenger(float $cashBalance = 2000): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => $cashBalance,
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

    public function test_disabled_feature_blocks_soft_hold(): void
    {
        config(['workride.soft_hold.enabled' => false]);

        $trip = $this->bookableTrip();
        $passenger = $this->passenger();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('trip');

        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('bookings', ['passenger_id' => $passenger->id]);
    }

    public function test_soft_hold_reserves_seat_and_holds_wallet(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $booking = $trip->bookings()->first();

        $this->assertNotNull($booking);
        $this->assertEquals(BookingStatus::SoftHold, $booking->status);
        $this->assertNotNull($booking->soft_hold_expires_at);
        $this->assertEquals(3, $trip->fresh()->available_seats);
        $this->assertEquals(1400, (float) $passenger->fresh()->wallet->cash_balance);

        $this->assertDatabaseHas('transactions', [
            'reference' => "BOOK-{$booking->id}-HOLD",
            'type' => 'hold',
            'amount' => 600,
        ]);
    }

    public function test_cash_soft_hold_reserves_seat_without_hold(): void
    {
        $passenger = $this->passenger(0);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'cash'])
            ->assertRedirect();

        $booking = $trip->bookings()->first();

        $this->assertEquals(BookingStatus::SoftHold, $booking->status);
        $this->assertEquals(3, $trip->fresh()->available_seats);
        $this->assertEquals(0, (float) $passenger->fresh()->wallet->cash_balance);
        $this->assertDatabaseMissing('transactions', ['reference' => "BOOK-{$booking->id}-HOLD"]);
    }

    public function test_soft_hold_rejects_own_trip_and_ride_credit(): void
    {
        $driver = $this->driver();
        $trip = $this->bookableTrip($driver);

        $this->actingAs($driver)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('trip');

        $passenger = $this->passenger(2000);
        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'ride_credit'])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_soft_hold_rejects_full_trip(): void
    {
        $trip = Trip::factory()->forDriver($this->driver())->create([
            'corridor' => Corridor::KubwaCbd,
            'total_seats' => 1,
            'available_seats' => 0,
            'departure_time' => now()->addMinutes(30),
        ]);

        $this->actingAs($this->passenger(2000))
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('trip');

        $this->actingAs($this->passenger(2000), 'sanctum')
            ->postJson("/api/v1/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertStatus(422);
    }

    public function test_confirm_soft_hold_commits_the_seat(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertRedirect();

        $booking = $trip->bookings()->first();

        $this->actingAs($passenger)
            ->post("/bookings/{$booking->id}/confirm-soft-hold")
            ->assertRedirect()
            ->assertSessionHas('status');

        $fresh = $booking->fresh();
        $this->assertEquals(BookingStatus::Confirmed, $fresh->status);
        $this->assertNull($fresh->soft_hold_expires_at);
        $this->assertEquals(3, $trip->fresh()->available_seats);
    }

    public function test_confirm_soft_hold_rejects_stranger(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet']);

        $booking = $trip->bookings()->first();

        $this->actingAs($this->passenger(2000))
            ->post("/bookings/{$booking->id}/confirm-soft-hold")
            ->assertSessionHasErrors('booking');
    }

    public function test_expired_hold_cannot_be_confirmed(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->travelTo(now()->subMinutes(4));
        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet']);

        $booking = $trip->bookings()->first();

        $this->travelTo(now()->addMinutes(5));
        $this->actingAs($passenger)
            ->post("/bookings/{$booking->id}/confirm-soft-hold")
            ->assertSessionHasErrors('booking');
    }

    public function test_release_expired_soft_holds_refunds_and_frees_seat(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet']);

        $booking = $trip->bookings()->first();

        $this->travelTo(now()->addMinutes(4));

        $released = $this->app->make(BookingService::class)->releaseExpiredSoftHolds();

        $this->assertSame(1, $released);
        $this->assertEquals(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertNull($booking->fresh()->soft_hold_expires_at);
        $this->assertEquals(4, $trip->fresh()->available_seats);
        $this->assertEquals(2000, (float) $passenger->fresh()->wallet->cash_balance);
    }

    public function test_release_job_releases_expired_holds(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet']);

        $booking = $trip->bookings()->first();

        $this->travelTo(now()->addMinutes(4));

        (new ReleaseExpiredSoftHoldsJob)->handle($this->app->make(BookingService::class));

        $this->assertEquals(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertEquals(4, $trip->fresh()->available_seats);
        $this->assertEquals(2000, (float) $passenger->fresh()->wallet->cash_balance);
    }

    public function test_release_skips_unexpired_and_disabled(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet']);

        $booking = $trip->bookings()->first();

        $this->travelTo(now()->addMinute());
        $released = $this->app->make(BookingService::class)->releaseExpiredSoftHolds();
        $this->assertSame(0, $released);

        config(['workride.soft_hold.enabled' => false]);
        $this->travelTo(now()->addMinutes(5));
        $released = $this->app->make(BookingService::class)->releaseExpiredSoftHolds();
        $this->assertSame(0, $released);
        $this->assertEquals(BookingStatus::SoftHold, $booking->fresh()->status);
    }

    public function test_api_soft_hold_and_confirm(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet'])
            ->assertStatus(201)
            ->assertJsonPath('booking.status', 'soft_hold');

        $booking = $trip->bookings()->first();

        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/bookings/{$booking->id}/confirm-soft-hold")
            ->assertOk()
            ->assertJsonPath('booking.status', 'confirmed');
    }

    public function test_trips_show_renders_hold_button_when_enabled(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('Hold my seat', false)
            ->assertSee('confirm from My Rides', false);
    }

    public function test_trips_show_hides_hold_button_when_disabled(): void
    {
        config(['workride.soft_hold.enabled' => false]);

        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertDontSee('Hold my seat', false);
    }

    public function test_my_rides_offers_confirm_for_held_seat(): void
    {
        $passenger = $this->passenger(2000);
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/soft-hold", ['payment_method' => 'wallet']);

        $booking = $trip->bookings()->first();

        $this->actingAs($passenger)
            ->get('/bookings')
            ->assertOk()
            ->assertSee('Confirm seat', false)
            ->assertSee(route('bookings.confirm-soft-hold', $booking), false);
    }
}
