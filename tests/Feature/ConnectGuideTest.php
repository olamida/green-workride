<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\TripWaypoint;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Services\ConnectGuideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConnectGuideTest extends TestCase
{
    use RefreshDatabase;

    private function driver(): User
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        Vehicle::factory()->create(['user_id' => $driver->id, 'plate_number' => 'ABJ-777-KJ']);
        Wallet::create(['user_id' => $driver->id]);

        return $driver;
    }

    private function passenger(): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create(['user_id' => $user->id]);

        return $user;
    }

    private function tripFor(User $driver, array $overrides = []): Trip
    {
        return Trip::factory()->forDriver($driver)->create(array_merge([
            'route_name' => 'Kubwa → CBD',
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addHour()->floorMinute(),
        ], $overrides));
    }

    private function book(User $passenger, Trip $trip): Booking
    {
        return Booking::factory()->create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Confirmed,
        ]);
    }

    public function test_guest_is_redirected_away_from_guide(): void
    {
        $trip = $this->tripFor($this->driver());

        $this->get("/trips/{$trip->id}/guide")->assertRedirect('/login');
    }

    public function test_non_participant_cannot_open_guide(): void
    {
        $trip = $this->tripFor($this->driver());

        $this->actingAs($this->passenger())
            ->get("/trips/{$trip->id}/guide")
            ->assertForbidden();
    }

    public function test_driver_can_open_guide_with_live_target(): void
    {
        $trip = $this->tripFor($this->driver(), [
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ]);

        $this->actingAs($trip->driver)
            ->get("/trips/{$trip->id}/guide")
            ->assertOk()
            ->assertSee('Connect guide')
            ->assertSee('connect-guide-map')
            ->assertSee('Your ride · ABJ-777-KJ');
    }

    public function test_confirmed_passenger_can_open_guide_and_audit_is_logged(): void
    {
        $trip = $this->tripFor($this->driver());
        $passenger = $this->passenger();
        $this->book($passenger, $trip);

        $this->actingAs($passenger)
            ->get("/trips/{$trip->id}/guide")
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $passenger->id,
            'action' => 'guide_opened',
            'model_type' => Trip::class,
            'model_id' => $trip->id,
        ]);
    }

    public function test_scheduled_trip_without_live_position_targets_next_waypoint(): void
    {
        $trip = $this->tripFor($this->driver(), [
            'current_lat' => null,
            'current_lng' => null,
        ]);

        TripWaypoint::create([
            'trip_id' => $trip->id,
            'label' => 'Berger Junction',
            'lat' => 9.08,
            'lng' => 7.46,
            'sequence' => 1,
        ]);

        $this->actingAs($trip->driver)
            ->get("/trips/{$trip->id}/guide")
            ->assertOk()
            ->assertSee('Berger Junction');
    }

    public function test_completed_or_cancelled_trip_has_no_guide(): void
    {
        $trip = $this->tripFor($this->driver(), ['status' => TripStatus::Completed]);

        $this->actingAs($trip->driver)
            ->get("/trips/{$trip->id}/guide")
            ->assertNotFound();
    }

    public function test_route_endpoint_returns_walking_route_for_participant(): void
    {
        $trip = $this->tripFor($this->driver(), [
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ]);
        $passenger = $this->passenger();
        $this->book($passenger, $trip);

        Http::fake([
            '*/route/v1/foot/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 420,
                    'duration' => 340,
                    'geometry' => ['coordinates' => [[7.45, 9.05], [7.46, 9.06]]],
                ]],
            ]),
        ]);

        $this->actingAs($passenger)
            ->getJson("/trips/{$trip->id}/guide/route?from_lat=9.05&from_lng=7.45")
            ->assertOk()
            ->assertJson([
                'distance_m' => 420,
                'duration_s' => 340,
                'provider' => 'osrm',
            ])
            ->assertJsonStructure(['points']);
    }

    public function test_route_endpoint_blocks_non_participant(): void
    {
        $trip = $this->tripFor($this->driver(), [
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ]);

        $this->actingAs($this->passenger())
            ->getJson("/trips/{$trip->id}/guide/route?from_lat=9.05&from_lng=7.45")
            ->assertForbidden();
    }

    public function test_route_endpoint_rejects_outside_fct(): void
    {
        $trip = $this->tripFor($this->driver());

        $this->actingAs($trip->driver)
            ->getJson("/trips/{$trip->id}/guide/route?from_lat=6.45&from_lng=3.39")
            ->assertStatus(422);
    }

    public function test_route_endpoint_rejects_when_no_target_shared(): void
    {
        $trip = $this->tripFor($this->driver(), [
            'current_lat' => null,
            'current_lng' => null,
        ]);

        $this->actingAs($trip->driver)
            ->getJson("/trips/{$trip->id}/guide/route?from_lat=9.05&from_lng=7.45")
            ->assertStatus(422);
    }

    public function test_service_walking_math_uses_configured_factor_and_speed(): void
    {
        config([
            'workride.guide.route_factor' => 1.25,
            'workride.guide.walking_speed_kmh' => 5,
            'workride.guide.arrived_radius_m' => 50,
        ]);

        $service = $this->app->make(ConnectGuideService::class);

        // ~1 km apart.
        $distance = $service->walkingDistanceM(9.0, 7.4, ['lat' => 9.05, 'lng' => 7.45, 'label' => 'x']);
        $this->assertNotNull($distance);
        $this->assertGreaterThan(1000, $distance);

        $this->assertSame(0, $service->walkingDurationS(0));
        $this->assertGreaterThan(0, $service->walkingDurationS(500));
        $this->assertTrue($service->isArrived(49));
        $this->assertFalse($service->isArrived(51));
    }

    public function test_service_walking_route_falls_back_to_straight_line(): void
    {
        Http::fake([
            '*/route/v1/foot/*' => Http::response([], 500),
        ]);

        $service = $this->app->make(ConnectGuideService::class);
        $route = $service->walkingRoute(
            ['lat' => 9.0, 'lng' => 7.4],
            ['lat' => 9.05, 'lng' => 7.45],
        );

        $this->assertSame('straight_line', $route['provider']);
        $this->assertGreaterThan(1000, $route['distance_m']);
        $this->assertCount(2, $route['points']);
    }

    public function test_service_target_for_trip_never_matches_zero_coordinates(): void
    {
        $trip = $this->tripFor($this->driver(), [
            'current_lat' => 0,
            'current_lng' => 0,
        ]);

        $target = $this->app->make(ConnectGuideService::class)->targetFor($trip);

        $this->assertNotSame('live', $target['type']);
    }
}
