<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\RideCreditStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\RideCredit;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('workride.time_bank.enabled', true);
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

    private function rideCreditPassenger(): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::NinVerified,
        ]);

        Vehicle::factory()->create(['user_id' => $user->id]);
        Wallet::create(['user_id' => $user->id]);

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

    public function test_time_bank_disabled_rejects_ride_credit(): void
    {
        config()->set('workride.time_bank.enabled', false);

        $passenger = $this->rideCreditPassenger();
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'ride_credit'])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseMissing('ride_credits', ['user_id' => $passenger->id]);
    }

    public function test_ride_credit_requires_nin_verification(): void
    {
        $passenger = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);
        Vehicle::factory()->create(['user_id' => $passenger->id]);
        Wallet::create(['user_id' => $passenger->id]);

        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'ride_credit'])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_ride_credit_requires_registered_vehicle(): void
    {
        $passenger = User::factory()->create([
            'verification_level' => VerificationLevel::NinVerified,
        ]);
        Wallet::create(['user_id' => $passenger->id]);

        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'ride_credit'])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_ride_credit_booking_creates_owed_seats_and_no_hold(): void
    {
        $passenger = $this->rideCreditPassenger();
        $trip = $this->bookableTrip(null, 600);

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'ride_credit'])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'passenger_id' => $passenger->id,
            'fare_paid' => 0,
            'payment_method' => 'ride_credit',
        ]);

        $this->assertDatabaseHas('ride_credits', [
            'user_id' => $passenger->id,
            'seats_owed' => 1,
            'seats_repaid' => 0,
            'status' => RideCreditStatus::Owed->value,
        ]);

        // No wallet hold — the fare was converted into seats owed.
        $this->assertSame('0.00', $passenger->wallet->fresh()->cash_balance);
    }

    public function test_max_owed_seats_are_enforced(): void
    {
        $passenger = $this->rideCreditPassenger();

        foreach (range(1, 3) as $i) {
            $this->actingAs($passenger)
                ->post('/trips/'.$this->bookableTrip()->id.'/book', ['payment_method' => 'ride_credit'])
                ->assertRedirect();
        }

        $this->actingAs($passenger)
            ->post('/trips/'.$this->bookableTrip()->id.'/book', ['payment_method' => 'ride_credit'])
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(
            3,
            RideCredit::where('user_id', $passenger->id)->where('status', RideCreditStatus::Owed->value)->count()
        );
    }

    public function test_cancelling_ride_credit_booking_waives_the_credit(): void
    {
        $passenger = $this->rideCreditPassenger();
        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'ride_credit'])
            ->assertRedirect();

        $booking = $trip->bookings()->where('passenger_id', $passenger->id)->firstOrFail();

        $this->actingAs($passenger)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect();

        $this->assertDatabaseHas('ride_credits', [
            'booking_id' => $booking->id,
            'status' => RideCreditStatus::Waived->value,
        ]);
    }

    public function test_overdue_ride_credit_blocks_new_ride_credit_bookings(): void
    {
        $passenger = $this->rideCreditPassenger();

        RideCredit::create([
            'user_id' => $passenger->id,
            'seats_owed' => 2,
            'seats_repaid' => 0,
            'fare_value' => 600,
            'due_date' => now()->subDay(),
            'status' => RideCreditStatus::Owed,
        ]);

        $trip = $this->bookableTrip();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'ride_credit'])
            ->assertSessionHasErrors('payment_method');

        // The overdue credit row is the durable gate — it survives the failed
        // booking's rolled-back transaction.
        $this->assertTrue(
            RideCredit::where('user_id', $passenger->id)
                ->where('status', RideCreditStatus::Owed->value)
                ->where('due_date', '<', now())
                ->exists()
        );
        $this->assertDatabaseMissing('bookings', ['passenger_id' => $passenger->id]);
    }

    public function test_completing_trip_repays_a_seat_per_passenger_carried(): void
    {
        $driver = $this->driver();

        RideCredit::create([
            'user_id' => $driver->id,
            'seats_owed' => 2,
            'seats_repaid' => 0,
            'fare_value' => 600,
            'due_date' => now()->addDays(7),
            'status' => RideCreditStatus::Owed,
        ]);

        $trip = $this->bookableTrip($driver, 600, 4);
        $passenger = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);
        Wallet::create(['user_id' => $passenger->id, 'cash_balance' => 2000]);

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'cash'])->assertRedirect();
        $booking = $trip->bookings()->where('passenger_id', $passenger->id)->firstOrFail();

        $this->actingAs($driver)->post("/trips/{$trip->id}/start")->assertRedirect();
        $this->actingAs($driver)->post("/bookings/{$booking->id}/board")->assertRedirect();
        $this->actingAs($driver)->post("/trips/{$trip->id}/complete")->assertRedirect();

        $this->assertDatabaseHas('ride_credits', [
            'user_id' => $driver->id,
            'seats_owed' => 2,
            'seats_repaid' => 1,
            'status' => RideCreditStatus::Owed->value,
        ]);
    }

    public function test_api_ride_credits_index_lists_owed_seats(): void
    {
        $passenger = $this->rideCreditPassenger();
        $trip = $this->bookableTrip();

        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/trips/{$trip->id}/bookings", ['payment_method' => 'ride_credit'])
            ->assertCreated();

        $this->actingAs($passenger, 'sanctum')
            ->getJson('/api/v1/ride-credits')
            ->assertOk()
            ->assertJsonPath('time_bank_enabled', true)
            ->assertJsonPath('outstanding_seats', 1);
    }
}
