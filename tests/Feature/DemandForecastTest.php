<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\ForecastEventType;
use App\Enums\UserRole;
use App\Jobs\CalculateDriverScoresJob;
use App\Models\Booking;
use App\Models\DemandRequest;
use App\Models\Junction;
use App\Models\OdSurvey;
use App\Models\ProbeDemandPoint;
use App\Models\Trip;
use App\Models\User;
use App\Models\Workplace;
use App\Services\DemandService;
use App\Services\ForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandForecastTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function junction(): Junction
    {
        return Junction::create([
            'name' => 'Berger Junction',
            'corridor' => 'nyanya_idu',
            'lat' => 9.03,
            'lng' => 7.433,
            'zone' => 'FCT-Keffi corridor',
            'is_active' => true,
        ]);
    }

    // --- API: manual junction counts (guide §9B Method 1) ---

    public function test_unauthenticated_cannot_submit_a_survey(): void
    {
        $this->postJson('/api/v1/demand/surveys', ['junction_id' => 1, 'count' => 50])
            ->assertUnauthorized();
    }

    public function test_verified_user_can_submit_a_junction_count(): void
    {
        $this->junction();
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/demand/surveys', [
                'junction_id' => Junction::first()->id,
                'count' => 320,
                'destination_text' => 'CBD',
                'hour' => 7,
                'day_type' => 'weekday',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('demand_surveys', [
            'junction_id' => Junction::first()->id,
            'count' => 320,
            'collected_by' => $user->id,
        ]);
    }

    // --- API: rider check-in (guide §9B Method 5) ---

    public function test_rider_checkin_is_recorded_inside_fct(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/demand/checkins', [
                'pickup_lat' => 9.05,
                'pickup_lng' => 7.45,
                'destination_text' => 'Federal Secretariat',
                'passengers_count' => 2,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('demand_requests', [
            'user_id' => $user->id,
            'passengers_count' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_rider_checkin_outside_fct_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/demand/checkins', [
                'pickup_lat' => 6.5244,
                'pickup_lng' => 3.3792,
                'destination_text' => 'Lagos Island',
                'passengers_count' => 1,
            ])
            ->assertStatus(422);
    }

    // --- API: probe dwell points (guide §9B Method 2) ---

    public function test_probe_points_merge_within_150m(): void
    {
        $user = $this->user();

        $payload = [
            'lat' => 9.05,
            'lng' => 7.45,
            'corridor' => 'kubwa_cbd',
            'avg_speed' => 4.5,
            'dwell_time_seconds' => 240,
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/demand/probes', $payload)->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/demand/probes', $payload)->assertCreated();

        $this->assertSame(1, ProbeDemandPoint::count());
        $this->assertSame(2, ProbeDemandPoint::first()->times_visited);
    }

    // --- OD matrix generation (guide §9B Method 3) ---

    public function test_od_matrix_is_generated_from_surveys(): void
    {
        $workplace = Workplace::factory()->create(['zone' => 'CBD']);

        OdSurvey::create(['workplace_id' => $workplace->id, 'home_area' => 'Kubwa', 'mode' => 'bus']);
        OdSurvey::create(['workplace_id' => $workplace->id, 'home_area' => 'Kubwa', 'mode' => 'keke']);

        $count = app(DemandService::class)->generateOdMatrix(['generated_by' => null]);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('od_matrix', [
            'origin_area' => 'Kubwa',
            'destination_area' => 'CBD',
            'count' => 2,
        ]);
    }

    // --- Web: rider check-in page ---

    public function test_rider_demand_page_renders(): void
    {
        $this->junction();
        $user = $this->user();
        DemandRequest::create([
            'user_id' => $user->id,
            'pickup_lat' => 9.05,
            'pickup_lng' => 7.45,
            'destination_text' => 'CBD',
            'passengers_count' => 1,
            'requested_at' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get('/demand')
            ->assertOk()
            ->assertSee('Demand check-in')
            ->assertSee('Berger Junction');
    }

    public function test_guest_is_redirected_from_demand_page(): void
    {
        $this->get('/demand')->assertRedirect('/login');
    }

    // --- Forecast service (guide §9) ---

    public function test_forecast_suggest_layers_multiplier_over_baseline(): void
    {
        $target = today();
        $corridor = Corridor::KubwaCbd->value;

        foreach ([1, 2, 3] as $weeksAgo) {
            $trip = Trip::factory()->create(['corridor' => $corridor, 'created_at' => now()->subWeeks($weeksAgo)]);
            Booking::factory()->create([
                'trip_id' => $trip->id,
                'created_at' => $target->copy()->subWeeks($weeksAgo)->setTime(8, 0),
                'status' => BookingStatus::Completed,
                'fare_paid' => 600,
            ]);
        }

        $suggestion = app(ForecastService::class)->suggest($target, $corridor, 2.0);

        $this->assertSame(0.8, $suggestion['baseline']); // 3 bookings/4 weeks
        $this->assertSame(1.5, $suggestion['predicted']); // 0.75 × 2.0 multiplier
        $this->assertSame(2.0, $suggestion['multiplier']);
    }

    // --- Admin pages ---

    public function test_admin_can_view_all_ops_pages(): void
    {
        $this->junction();

        $this->actingAs($this->admin())
            ->get('/admin/ops/demand')->assertOk()->assertSee('Demand Research');

        $this->actingAs($this->admin())
            ->get('/admin/fleet')->assertOk()->assertSee('Fleet');

        $this->actingAs($this->admin())
            ->get('/admin/forecasts')->assertOk()->assertSee('Demand Calendar');

        $this->actingAs($this->admin())
            ->get('/admin/stakeholders')->assertOk()->assertSee('Stakeholder');

        $this->actingAs($this->admin())
            ->get('/admin/driver-scores')->assertOk()->assertSee('Driver Scoreboard');
    }

    public function test_non_admin_cannot_access_ops_pages(): void
    {
        $user = $this->user();

        foreach (['/admin/ops/demand', '/admin/fleet', '/admin/forecasts', '/admin/stakeholders', '/admin/driver-scores'] as $path) {
            $this->actingAs($user)->get($path)->assertForbidden();
        }
    }

    public function test_admin_can_log_a_forecast_event_with_default_multiplier(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/forecasts', [
                'date' => today()->addDays(2)->toDateString(),
                'event_type' => ForecastEventType::Govt->value,
                'event_name' => 'FAAC payment week',
                'corridor' => Corridor::KubwaCbd->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('forecasts', [
            'event_name' => 'FAAC payment week',
            'expected_demand_multiplier' => 1.6,
            'corridor' => Corridor::KubwaCbd->value,
        ]);
    }

    public function test_driver_score_job_writes_weekly_snapshot(): void
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'green_points' => 50,
        ]);

        Trip::factory()->forDriver($driver)->create(['status' => 'completed', 'updated_at' => now()]);

        $count = (new CalculateDriverScoresJob)->handle();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('driver_scores', [
            'user_id' => $driver->id,
            'rides_completed' => 1,
            'green_points_earned' => 50,
        ]);
    }
}
