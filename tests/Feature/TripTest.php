<?php

namespace Tests\Feature;

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

class TripTest extends TestCase
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

    private function tripData(array $overrides = []): array
    {
        return array_merge([
            'corridor' => 'kubwa_cbd',
            'origin_text' => 'Kubwa Junction',
            'destination_text' => 'Federal Secretariat',
            'total_seats' => 4,
            'departure_time' => now()->addHour()->format('Y-m-d H:i:s'),
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ], $overrides);
    }

    public function test_guest_cannot_access_trips_board(): void
    {
        $this->get('/trips')->assertRedirect('/login');
    }

    public function test_verified_user_can_view_trips_board(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Kubwa → CBD',
            'departure_time' => now()->addMinutes(15),
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Kubwa → CBD');
    }

    public function test_unverified_user_cannot_publish_paid_trip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/trips', $this->tripData())
            ->assertForbidden();
    }

    public function test_verified_volunteer_can_publish_free_ride(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Volunteer,
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        $this->actingAs($user)
            ->post('/trips', $this->tripData(['is_free_volunteer' => '1']))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('trips', [
            'driver_id' => $user->id,
            'corridor' => Corridor::KubwaCbd->value,
            'is_free_volunteer' => 1,
            'fare_per_seat' => 0,
        ]);
    }

    public function test_driver_can_publish_paid_trip_with_fixed_fare(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)
            ->post('/trips', $this->tripData())
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('trips', [
            'driver_id' => $driver->id,
            'corridor' => Corridor::KubwaCbd->value,
            'fare_per_seat' => 800,
            'is_free_volunteer' => 0,
        ]);
    }

    public function test_publish_requires_future_departure(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)
            ->post('/trips', $this->tripData(['departure_time' => now()->subDay()->format('Y-m-d H:i:s')]))
            ->assertSessionHasErrors('departure_time');
    }

    public function test_publish_outside_fct_is_rejected(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)
            ->post('/trips', $this->tripData(['current_lat' => 12.0, 'current_lng' => 8.0]))
            ->assertSessionHasErrors('current_lat');
    }

    public function test_driver_cannot_publish_with_someone_elses_vehicle(): void
    {
        $driver = $this->driver();
        $other = $this->driver();

        $this->actingAs($driver)
            ->post('/trips', $this->tripData(['vehicle_id' => $other->vehicles()->first()->id]))
            ->assertSessionHasErrors('vehicle_id');
    }

    public function test_api_search_filters_by_corridor_and_distance(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addMinutes(10),
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ]);
        Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::LugbeCbd,
            'departure_time' => now()->addMinutes(10),
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ]);

        $this->actingAs($this->verifiedWorker(), 'sanctum')
            ->getJson('/api/v1/trips?corridor=kubwa_cbd&from_lat=9.05&from_lng=7.45')
            ->assertOk()
            ->assertJsonCount(1, 'trips')
            ->assertJsonPath('trips.0.id', $trip->id);
    }

    public function test_driver_can_start_trip(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();

        $this->actingAs($driver)
            ->post("/trips/{$trip->id}/start")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(TripStatus::Active, $trip->fresh()->status);
    }

    public function test_only_driver_can_start_trip(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();

        $this->actingAs($this->verifiedWorker())
            ->post("/trips/{$trip->id}/start")
            ->assertSessionHasErrors('trip');

        $this->assertEquals(TripStatus::Scheduled, $trip->fresh()->status);
    }

    public function test_driver_can_complete_active_trip(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create(['status' => TripStatus::Active]);

        $this->actingAs($driver)
            ->post("/trips/{$trip->id}/complete")
            ->assertRedirect();

        $this->assertEquals(TripStatus::Completed, $trip->fresh()->status);
    }

    public function test_driver_can_cancel_scheduled_trip(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();

        $this->actingAs($driver)
            ->post("/trips/{$trip->id}/cancel")
            ->assertRedirect();

        $this->assertEquals(TripStatus::Cancelled, $trip->fresh()->status);
    }

    public function test_guest_cannot_view_trip_detail(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();

        $this->get("/trips/{$trip->id}")->assertRedirect('/login');
    }
}
