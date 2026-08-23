<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\DemandDayType;
use App\Enums\TripStatus;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\DemandRequest;
use App\Models\DemandSurvey;
use App\Models\Junction;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

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

    private function scheduledTrip(User $driver, array $overrides = []): Trip
    {
        return Trip::factory()->forDriver($driver)->create(array_merge([
            'status' => TripStatus::Scheduled,
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addMinutes(30),
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login_from_go(): void
    {
        $this->get('/go')->assertRedirect('/login');
    }

    public function test_authenticated_go_renders_search_and_never_empty_map(): void
    {
        $driver = $this->driver();
        $this->scheduledTrip($driver, ['route_name' => 'Kubwa → CBD']);

        $this->actingAs($this->user())
            ->get('/go')
            ->assertOk()
            ->assertSee('Where are you going?')
            ->assertSee('go-map')
            ->assertSee('initGoMap')
            ->assertSee('Kubwa → CBD')
            ->assertSee('whereTo')
            ->assertSee('data-corridor-chip');
    }

    public function test_go_map_renders_even_when_board_is_empty(): void
    {
        $this->actingAs($this->user())
            ->get('/go')
            ->assertOk()
            ->assertSee('go-map')
            ->assertSee('initGoMap');
    }

    public function test_api_search_returns_junction_with_demand_volume(): void
    {
        Http::fake(['*' => Http::response([])]);

        $junction = Junction::create([
            'name' => 'Berger Junction',
            'corridor' => 'kubwa_cbd',
            'lat' => 9.064,
            'lng' => 7.49,
            'zone' => 'Wuse',
            'is_active' => true,
        ]);

        DemandSurvey::create([
            'junction_id' => $junction->id,
            'count' => 320,
            'destination_text' => 'CBD',
            'hour' => 7,
            'day_type' => DemandDayType::Weekday,
            'lat' => 9.064,
            'lng' => 7.49,
        ]);

        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/navigation/search?q=Berger')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Berger Junction')
            ->assertJsonPath('data.0.type', 'junction')
            ->assertJsonPath('data.0.passenger_volume_daily', 320);
    }

    public function test_api_directions_returns_route_trips_and_demand(): void
    {
        Http::fake([
            '*/route/v1/driving/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 18000,
                    'duration' => 1800,
                    'geometry' => ['coordinates' => [[7.3304, 9.1117], [7.4900, 9.0500]]],
                ]],
            ]),
        ]);

        $driver = $this->driver();
        $this->scheduledTrip($driver, ['route_name' => 'Kubwa → CBD']);

        DemandRequest::create([
            'user_id' => $this->user()->id,
            'pickup_lat' => 9.05,
            'pickup_lng' => 7.45,
            'destination_text' => 'the CBD',
            'passengers_count' => 2,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/navigation/directions?from_lat=9.05&from_lng=7.45&to_lat=9.0589&to_lng=7.4891')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'route' => ['geometry', 'distance_km', 'duration_min', 'provider'],
                    'trips',
                    'demand' => ['people', 'top_destinations'],
                ],
            ])
            ->assertJsonPath('data.demand.people', 2);
    }

    public function test_api_nearby_returns_only_trips_within_radius(): void
    {
        $driver = $this->driver();
        $near = $this->scheduledTrip($driver, ['route_name' => 'Nearby ride', 'current_lat' => 9.05, 'current_lng' => 7.45]);

        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/navigation/nearby?lat=9.05&lng=7.45&radius=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $near->id);
    }

    public function test_share_referral_attributes_booking_via_session(): void
    {
        $driver = $this->driver();
        $trip = $this->scheduledTrip($driver, ['route_name' => 'Kubwa → CBD']);
        $referrer = $this->user();
        $passenger = $this->user();

        $this->get("/trips/{$trip->id}/share?ref={$referrer->id}")
            ->assertOk()
            ->assertSee($trip->share_code);

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/book", [
                'payment_method' => 'cash',
                'pickup_lat' => 9.05,
                'pickup_lng' => 7.45,
            ])
            ->assertRedirect(route('bookings.index'));

        $booking = Booking::where('trip_id', $trip->id)->where('passenger_id', $passenger->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals($referrer->id, $booking->referred_by_user_id);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'booking_referred',
            'model_type' => Booking::class,
            'model_id' => $booking->id,
        ]);
    }

    public function test_share_referral_is_not_consumed_when_driver_shares_own_ride(): void
    {
        $driver = $this->driver();
        $trip = $this->scheduledTrip($driver, ['route_name' => 'Kubwa → CBD']);

        $this->get("/trips/{$trip->id}/share?ref={$driver->id}")
            ->assertOk();

        $this->actingAs($this->user())
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'cash'])
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseMissing('bookings', ['referred_by_user_id' => $driver->id]);
    }
}
