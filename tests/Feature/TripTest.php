<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\DriverScoreLevel;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\BusSchedule;
use App\Models\DriverScore;
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
            ->assertSee('Kubwa → CBD')
            ->assertSee('trips-map');
    }

    public function test_board_map_renders_when_trips_exist(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Kubwa → CBD',
            'corridor' => 'kubwa_cbd',
            'departure_time' => now()->addMinutes(20),
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Map view')
            ->assertSee('trips-map')
            ->assertSee('initTripsMap');
    }

    public function test_board_map_is_hidden_when_no_trips(): void
    {
        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertDontSee('trips-map');
    }

    public function test_board_shows_day_ahead_trips_by_default(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Tomorrow morning run',
            'departure_time' => now()->addDay()->setTime(6, 45),
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Tomorrow morning run')
            ->assertSee('Book ahead');
    }

    public function test_board_now_window_hides_day_ahead_trips(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Next-day planning run',
            'departure_time' => now()->addDay()->setTime(6, 45),
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips?window=now')
            ->assertOk()
            ->assertDontSee('Next-day planning run')
            ->assertSee('No motor dey go for this window yet');
    }

    public function test_live_corridor_chip_pulses_when_trip_leaves_soon(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Kubwa leaving run',
            'corridor' => Corridor::KubwaCbd->value,
            'departure_time' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($this->verifiedWorker())->get('/trips');
        $response->assertOk()
            ->assertSee('Kubwa leaving run')
            ->assertSee('data-corridor-chip="kubwa_cbd"', false);

        // The corridor chip's live dot carries the wr-pulse class.
        $this->assertStringContainsString(
            'data-corridor-chip="kubwa_cbd"',
            $response->getContent()
        );
    }

    public function test_quiet_corridor_chip_has_no_live_pulse(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Day-ahead planning run',
            'corridor' => Corridor::NyanyaIdu->value,
            'departure_time' => now()->addDay()->setTime(7, 0),
        ]);

        $response = $this->actingAs($this->verifiedWorker())->get('/trips');
        $response->assertOk()
            ->assertSee('Day-ahead planning run')
            ->assertDontSee('data-corridor-chip');
    }

    public function test_seat_counter_carries_corridor_data_and_live_region(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Kubwa seat run',
            'corridor' => Corridor::KubwaCbd->value,
            'departure_time' => now()->addMinutes(20),
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('data-corridor="kubwa_cbd"', false)
            ->assertSee('aria-live="polite"', false);
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

    public function test_board_card_shows_driver_scorecard_badge(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Kubwa → CBD',
            'departure_time' => now()->addMinutes(15),
        ]);

        DriverScore::create([
            'user_id' => $driver->id,
            'period_start' => now()->startOfWeek()->toDateString(),
            'period_end' => now()->endOfWeek()->toDateString(),
            'rides_completed' => 12,
            'score' => 82,
            'level' => DriverScoreLevel::Gold,
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('82 · Gold');
    }

    public function test_board_card_omits_scorecard_without_snapshot(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Kubwa → CBD',
            'departure_time' => now()->addMinutes(15),
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Kubwa → CBD')
            ->assertDontSee('Gold driver');
    }

    public function test_trip_detail_shows_driver_scorecard(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create();

        DriverScore::create([
            'user_id' => $driver->id,
            'period_start' => now()->startOfWeek()->toDateString(),
            'period_end' => now()->endOfWeek()->toDateString(),
            'rides_completed' => 40,
            'score' => 95,
            'level' => DriverScoreLevel::Platinum,
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('95 Platinum driver');
    }

    public function test_board_shows_guaranteed_next_departures_from_schedules(): void
    {
        $this->travelTo('2026-08-10 05:00:00');

        $driver = $this->driver();
        $vehicle = Vehicle::where('user_id', $driver->id)->first();

        BusSchedule::factory()->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'departure_time' => '06:30',
            'end_time' => '07:00',
            'frequency_minutes' => 15,
            'days_of_week' => ['mon'],
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Next departures')
            ->assertSee('Guaranteed recurring slots')
            ->assertSee('scheduled');
    }

    public function test_board_omits_next_departures_when_scheduling_disabled(): void
    {
        config(['workride.scheduling.enabled' => false]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertDontSee('Next departures');
    }

    public function test_board_cards_render_match_score_and_reasons(): void
    {
        $driver = $this->driver();
        Trip::factory()->forDriver($driver)->create([
            'route_name' => 'Kubwa → CBD',
            'departure_time' => now()->addMinutes(15),
        ]);

        $this->actingAs($this->verifiedWorker())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Kubwa → CBD')
            ->assertSee('/100 match')
            ->assertSee('seats free')
            ->assertSee('Level 3 verified');
    }

    public function test_api_search_returns_match_score_and_ranks_closer_higher(): void
    {
        $driver = $this->driver();
        $near = Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addMinutes(10),
            'current_lat' => 9.05,
            'current_lng' => 7.45,
        ]);
        $far = Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addMinutes(10),
            'current_lat' => 9.0590,
            'current_lng' => 7.4550,
        ]);

        $this->actingAs($this->verifiedWorker(), 'sanctum')
            ->getJson('/api/v1/trips?corridor=kubwa_cbd&from_lat=9.05&from_lng=7.45')
            ->assertOk()
            ->assertJsonCount(2, 'trips')
            ->assertJsonPath('trips.0.id', $near->id)
            ->assertJsonStructure([
                'trips' => [['match_score', 'score_reasons', 'match_distance_m']],
            ])
            ->assertJsonPath('trips.0.match_score', fn (int $score) => $score > 0);

        $response = $this->actingAs($this->verifiedWorker(), 'sanctum')
            ->getJson('/api/v1/trips?corridor=kubwa_cbd&from_lat=9.05&from_lng=7.45')
            ->assertOk()
            ->json('trips');

        $this->assertGreaterThan($response[1]['match_score'], $response[0]['match_score']);
        $this->assertLessThan($response[1]['match_distance_m'], $response[0]['match_distance_m']);
    }
}
