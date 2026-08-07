<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\DriverPromptStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Jobs\CalculateDriverPromptsJob;
use App\Models\DemandRequest;
use App\Models\DriverPrompt;
use App\Models\Junction;
use App\Models\Trip;
use App\Models\TripTemplate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Notifications\DriverDemandPrompt;
use App\Services\DriverPromptService;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DriverToolingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.trip_templates.enabled' => true]);
        config(['workride.driver_prompts.enabled' => true]);
        config(['workride.driver_prompts.window_hours' => 2]);
        config(['workride.driver_prompts.min_passengers' => 10]);
        config(['workride.driver_prompts.supply_divisor' => 3]);
        config(['workride.driver_prompts.supply_window_hours' => 3]);
        config(['workride.driver_prompts.affinity_days' => 14]);
        config(['workride.driver_prompts.prompt_limit' => 5]);
    }

    private function driver(array $overrides = []): User
    {
        $driver = User::factory()->create(array_merge([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ], $overrides));

        Vehicle::factory()->create(['user_id' => $driver->id]);
        Wallet::create(['user_id' => $driver->id]);

        return $driver;
    }

    private function template(User $driver, array $overrides = []): TripTemplate
    {
        return TripTemplate::factory()->create(array_merge([
            'driver_id' => $driver->id,
            'corridor' => Corridor::KubwaCbd,
            'origin_text' => 'Kubwa Junction',
            'destination_text' => 'Federal Secretariat',
            'departure_time' => '06:45',
            'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
        ], $overrides));
    }

    private function junction(Corridor $corridor = Corridor::KubwaCbd, float $lat = 9.05, float $lng = 7.45): Junction
    {
        return Junction::create([
            'name' => $corridor->label().' Junction',
            'corridor' => $corridor->value,
            'lat' => $lat,
            'lng' => $lng,
            'zone' => 'FCT',
            'is_active' => true,
        ]);
    }

    private function checkIn(float $lat, float $lng, int $people = 5): void
    {
        DemandRequest::create([
            'user_id' => User::factory()->create()->id,
            'pickup_lat' => $lat,
            'pickup_lng' => $lng,
            'destination_text' => 'CBD',
            'passengers_count' => $people,
            'requested_at' => now(),
            'status' => 'pending',
        ]);
    }

    // --- Trip templates: page + gating ---

    public function test_guest_is_redirected_from_templates(): void
    {
        $this->get('/templates')->assertRedirect('/login');
    }

    public function test_templates_page_renders_off_notice_when_disabled(): void
    {
        config(['workride.trip_templates.enabled' => false]);

        $driver = $this->driver();
        $this->template($driver);

        $this->actingAs($driver)
            ->get('/templates')
            ->assertOk()
            ->assertSee('Trip templates are disabled')
            ->assertDontSee('New commute');
    }

    public function test_templates_index_lists_only_own_commutes(): void
    {
        $driver = $this->driver();
        $mine = $this->template($driver, ['name' => 'Morning Kubwa run']);

        $stranger = $this->driver();
        $this->template($stranger, ['name' => 'Stranger commute']);

        $this->actingAs($driver)
            ->get('/templates')
            ->assertOk()
            ->assertSee('Morning Kubwa run')
            ->assertSee('KUB-CBD')
            ->assertDontSee('Stranger commute');
    }

    public function test_driver_can_store_a_template(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)
            ->post('/templates', [
                'name' => 'Morning Kubwa run',
                'corridor' => 'kubwa_cbd',
                'origin_text' => 'Kubwa Junction',
                'destination_text' => 'Federal Secretariat',
                'departure_time' => '06:45',
                'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
                'total_seats' => 4,
                'is_free_volunteer' => false,
            ])
            ->assertRedirect(route('templates.index'));

        $this->assertDatabaseHas('trip_templates', [
            'driver_id' => $driver->id,
            'name' => 'Morning Kubwa run',
            'corridor' => 'kubwa_cbd',
            'departure_time' => '06:45',
        ]);
    }

    public function test_store_template_is_blocked_when_disabled(): void
    {
        config(['workride.trip_templates.enabled' => false]);

        $driver = $this->driver();

        $this->actingAs($driver)
            ->post('/templates', [
                'corridor' => 'kubwa_cbd',
                'origin_text' => 'Kubwa',
                'destination_text' => 'CBD',
                'departure_time' => '06:45',
                'total_seats' => 4,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('trip_templates', 0);
    }

    // --- Trip templates: one-tap publish ---

    public function test_publish_template_creates_a_trip_through_trip_service(): void
    {
        $driver = $this->driver();
        $template = $this->template($driver);

        Carbon::setTestNow(Carbon::parse('2026-08-03 05:00:00')); // Monday

        $this->actingAs($driver)
            ->post("/templates/{$template->id}/publish")
            ->assertRedirect();

        $trip = Trip::where('driver_id', $driver->id)->first();

        $this->assertNotNull($trip);
        $this->assertSame(Corridor::KubwaCbd, $trip->corridor);
        $this->assertSame('Kubwa Junction', $trip->origin_text);
        $this->assertSame('Federal Secretariat', $trip->destination_text);
        $this->assertSame(TripStatus::Scheduled, $trip->status);

        // Template used counter stamped.
        $this->assertSame(1, $template->fresh()->times_used);
    }

    public function test_publish_template_uses_pricing_service_fare_not_template_fare(): void
    {
        $driver = $this->driver();
        $template = $this->template($driver, ['fare_per_seat' => 9999]);

        Carbon::setTestNow(Carbon::parse('2026-08-03 05:00:00'));

        $this->actingAs($driver)->post("/templates/{$template->id}/publish")->assertRedirect();

        $trip = Trip::where('driver_id', $driver->id)->first();

        $this->assertNotSame(9999, (int) $trip->fare_per_seat);
        $this->assertSame((int) app(PricingService::class)->fareFor(Corridor::KubwaCbd, false), (int) $trip->fare_per_seat);
    }

    public function test_publish_template_with_no_run_day_is_rejected(): void
    {
        $driver = $this->driver();
        $template = $this->template($driver, ['days' => ['sat']]);

        Carbon::setTestNow(Carbon::parse('2026-08-03 06:00:00')); // Monday, template runs Saturday only

        $this->actingAs($driver)
            ->post("/templates/{$template->id}/publish")
            ->assertSessionHasErrors('template');

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_publish_week_materialises_companion_trips(): void
    {
        $driver = $this->driver();
        $template = $this->template($driver);

        Carbon::setTestNow(Carbon::parse('2026-08-03 05:00:00')); // Monday

        $this->actingAs($driver)
            ->post("/templates/{$template->id}/publish-week")
            ->assertRedirect(route('templates.index'));

        // Mon–Fri within the repeat horizon → 5 trips sharing a repeat_group.
        $trips = Trip::where('driver_id', $driver->id)->get();

        $this->assertSame(5, $trips->count());
        $this->assertSame(1, $trips->pluck('repeat_group')->unique()->count());
        $this->assertNotNull($trips->first()->repeat_group);
    }

    public function test_publish_template_is_owner_only(): void
    {
        $owner = $this->driver();
        $template = $this->template($owner);

        $stranger = $this->driver();

        $this->actingAs($stranger)
            ->post("/templates/{$template->id}/publish")
            ->assertSessionHasErrors('template');

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_driver_can_destroy_own_template_only(): void
    {
        $owner = $this->driver();
        $template = $this->template($owner);

        $stranger = $this->driver();

        $this->actingAs($stranger)
            ->delete("/templates/{$template->id}")
            ->assertSessionHasErrors('template');

        $this->assertDatabaseCount('trip_templates', 1);

        $this->actingAs($owner)
            ->delete("/templates/{$template->id}")
            ->assertRedirect(route('templates.index'));

        $this->assertDatabaseCount('trip_templates', 0);
    }

    // --- Save this trip as a template ---

    public function test_publishing_a_trip_with_save_template_creates_a_commute(): void
    {
        $driver = $this->driver();
        $vehicle = $driver->vehicles()->first();

        $this->actingAs($driver)
            ->post('/trips', [
                'corridor' => 'kubwa_cbd',
                'origin_text' => 'Kubwa Junction',
                'destination_text' => 'Federal Secretariat',
                'total_seats' => 4,
                'departure_time' => now()->addDay()->setTime(7, 0)->format('Y-m-d H:i'),
                'vehicle_id' => $vehicle->id,
                'save_template' => '1',
                'template_name' => 'Morning Kubwa run',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('trip_templates', [
            'driver_id' => $driver->id,
            'name' => 'Morning Kubwa run',
            'corridor' => 'kubwa_cbd',
            'route_name' => 'Kubwa → CBD',
        ]);
    }

    public function test_save_template_is_idempotent_per_route(): void
    {
        $driver = $this->driver();
        $vehicle = $driver->vehicles()->first();

        $payload = [
            'corridor' => 'kubwa_cbd',
            'origin_text' => 'Kubwa Junction',
            'destination_text' => 'Federal Secretariat',
            'total_seats' => 4,
            'departure_time' => now()->addDay()->setTime(7, 0)->format('Y-m-d H:i'),
            'vehicle_id' => $vehicle->id,
            'save_template' => '1',
        ];

        $this->actingAs($driver)->post('/trips', $payload)->assertRedirect();
        $this->actingAs($driver)->post('/trips', $payload)->assertRedirect();

        $this->assertSame(1, TripTemplate::where('driver_id', $driver->id)->count());
    }

    public function test_board_create_shows_saved_commutes_strip(): void
    {
        $driver = $this->driver();
        $this->template($driver, ['name' => 'Morning Kubwa run']);

        $this->actingAs($driver)
            ->get('/trips/create')
            ->assertOk()
            ->assertSee('Saved commutes')
            ->assertSee('Morning Kubwa run')
            ->assertSee('publish →');
    }

    // --- Driver prompts: service behaviour ---

    public function test_prompt_for_corridor_creates_and_notifies_idempotently(): void
    {
        Notification::fake();

        $this->junction();
        $this->checkIn(9.05, 7.45, 12);

        $driver = $this->driver();

        $service = app(DriverPromptService::class);

        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00'));
        $first = $service->promptForCorridor(Corridor::KubwaCbd);

        $this->assertSame(1, $first);
        $this->assertDatabaseHas('driver_prompts', [
            'driver_id' => $driver->id,
            'corridor' => 'kubwa_cbd',
            'people_count' => 12,
            'status' => 'prompted',
        ]);

        Notification::assertSentTo($driver, DriverDemandPrompt::class);

        // Second evaluation on the same day is a no-op (idempotency reference).
        $second = $service->promptForCorridor(Corridor::KubwaCbd);
        $this->assertSame(0, $second);
        $this->assertSame(1, DriverPrompt::count());

        Notification::assertSentToTimes($driver, DriverDemandPrompt::class, 1);
    }

    public function test_prompt_for_corridor_is_a_no_op_below_threshold(): void
    {
        $this->junction();
        $this->checkIn(9.05, 7.45, 2); // below min_passengers

        $this->driver();

        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00'));

        $this->assertSame(0, app(DriverPromptService::class)->promptForCorridor(Corridor::KubwaCbd));
        $this->assertDatabaseCount('driver_prompts', 0);
    }

    public function test_prompt_for_corridor_is_a_no_op_when_supply_covers_demand(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00'));

        $this->junction();
        $this->checkIn(9.05, 7.45, 12);

        $driver = $this->driver();

        Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'available_seats' => 30,
            'total_seats' => 30,
            'departure_time' => now()->addHour(),
        ]);

        // demand 12, supply 30 → 30 < 12/3 is false → no trigger.
        $this->assertFalse(app(DriverPromptService::class)->triggersFor(Corridor::KubwaCbd));
    }

    public function test_qualified_drivers_exclude_banned_and_active_drivers(): void
    {
        $idle = $this->driver();

        $banned = $this->driver(['is_banned' => true]);

        $active = $this->driver();
        Trip::factory()->forDriver($active)->create([
            'corridor' => Corridor::KubwaCbd,
            'status' => TripStatus::Active,
            'departure_time' => now()->addHour(),
        ]);

        $unverified = User::factory()->create(['verification_level' => VerificationLevel::Unverified]);

        $qualified = app(DriverPromptService::class)->qualifiedDrivers(Corridor::KubwaCbd);

        $this->assertTrue($qualified->contains('id', $idle->id));
        $this->assertFalse($qualified->contains('id', $banned->id));
        $this->assertFalse($qualified->contains('id', $active->id));
        $this->assertFalse($qualified->contains('id', $unverified->id));
    }

    public function test_qualified_drivers_prefer_corridor_affinity(): void
    {
        $affinity = $this->driver();
        Trip::factory()->forDriver($affinity)->create([
            'corridor' => Corridor::KubwaCbd,
            'status' => TripStatus::Completed,
            'departure_time' => now()->subDays(2),
        ]);

        $fresh = $this->driver();

        $qualified = app(DriverPromptService::class)->qualifiedDrivers(Corridor::KubwaCbd);

        $this->assertSame($affinity->id, $qualified->first()->id);
    }

    public function test_active_for_returns_only_recent_prompts(): void
    {
        $driver = $this->driver();

        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00'));

        $recent = DriverPrompt::create([
            'driver_id' => $driver->id,
            'corridor' => 'kubwa_cbd',
            'people_count' => 12,
            'status' => DriverPromptStatus::Prompted,
            'reference' => 'PROMPT-RECENT',
            'notified_at' => now(),
        ]);

        $stale = DriverPrompt::create([
            'driver_id' => $driver->id,
            'corridor' => 'nyanya_idu',
            'people_count' => 15,
            'status' => DriverPromptStatus::Prompted,
            'reference' => 'PROMPT-STALE',
            'notified_at' => now()->subDays(3),
        ]);

        $active = app(DriverPromptService::class)->activeFor($driver);

        $this->assertTrue($active->contains('id', $recent->id));
        $this->assertFalse($active->contains('id', $stale->id));
    }

    // --- Driver prompts: web accept/dismiss ---

    public function test_board_shows_demand_wants_you_panel_for_open_prompt(): void
    {
        $driver = $this->driver();

        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00'));

        DriverPrompt::create([
            'driver_id' => $driver->id,
            'corridor' => 'kubwa_cbd',
            'people_count' => 12,
            'status' => DriverPromptStatus::Prompted,
            'reference' => 'PROMPT-BOARD',
            'notified_at' => now(),
        ]);

        $this->actingAs($driver)
            ->get('/trips')
            ->assertOk()
            ->assertSee('Demand wants you')
            ->assertSee('12 people waiting')
            ->assertSee('Publish on this corridor →');
    }

    public function test_board_hides_panel_when_feature_disabled(): void
    {
        config(['workride.driver_prompts.enabled' => false]);

        $driver = $this->driver();

        DriverPrompt::create([
            'driver_id' => $driver->id,
            'corridor' => 'kubwa_cbd',
            'people_count' => 12,
            'status' => DriverPromptStatus::Prompted,
            'reference' => 'PROMPT-HIDDEN',
            'notified_at' => now(),
        ]);

        $this->actingAs($driver)
            ->get('/trips')
            ->assertOk()
            ->assertDontSee('Demand wants you');
    }

    public function test_accept_prompt_redirects_to_publish_with_corridor(): void
    {
        $driver = $this->driver();

        $prompt = DriverPrompt::create([
            'driver_id' => $driver->id,
            'corridor' => 'kubwa_cbd',
            'people_count' => 12,
            'status' => DriverPromptStatus::Prompted,
            'reference' => 'PROMPT-ACCEPT',
            'notified_at' => now(),
        ]);

        $this->actingAs($driver)
            ->post("/prompts/{$prompt->id}/accept")
            ->assertRedirect(route('trips.create', ['corridor' => 'kubwa_cbd']));

        $this->assertSame(DriverPromptStatus::Accepted, $prompt->fresh()->status);
        $this->assertNotNull($prompt->fresh()->accepted_at);
    }

    public function test_dismiss_prompt_is_owner_only(): void
    {
        $owner = $this->driver();
        $prompt = DriverPrompt::create([
            'driver_id' => $owner->id,
            'corridor' => 'kubwa_cbd',
            'people_count' => 12,
            'status' => DriverPromptStatus::Prompted,
            'reference' => 'PROMPT-OWNER',
            'notified_at' => now(),
        ]);

        $stranger = $this->driver();

        $this->actingAs($stranger)
            ->post("/prompts/{$prompt->id}/dismiss")
            ->assertForbidden();

        $this->assertSame(DriverPromptStatus::Prompted, $prompt->fresh()->status);

        $this->actingAs($owner)
            ->post("/prompts/{$prompt->id}/dismiss")
            ->assertRedirect();

        $this->assertSame(DriverPromptStatus::Dismissed, $prompt->fresh()->status);
    }

    public function test_accept_prompt_is_owner_only(): void
    {
        $owner = $this->driver();
        $prompt = DriverPrompt::create([
            'driver_id' => $owner->id,
            'corridor' => 'kubwa_cbd',
            'people_count' => 12,
            'status' => DriverPromptStatus::Prompted,
            'reference' => 'PROMPT-ACCEPT-OWNER',
            'notified_at' => now(),
        ]);

        $stranger = $this->driver();

        $this->actingAs($stranger)
            ->post("/prompts/{$prompt->id}/accept")
            ->assertForbidden();
    }

    // --- Driver prompts: admin nudge + job ---

    public function test_admin_nudge_prompts_qualified_drivers(): void
    {
        Notification::fake();

        $this->junction();
        $this->checkIn(9.05, 7.45, 12);

        $driver = $this->driver();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00'));

        $this->actingAs($admin)
            ->post('/admin/ops/demand/nudge')
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('driver_prompts', [
            'driver_id' => $driver->id,
            'corridor' => 'kubwa_cbd',
        ]);
    }

    public function test_admin_nudge_is_admin_only(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)
            ->post('/admin/ops/demand/nudge')
            ->assertForbidden();
    }

    public function test_calculate_driver_prompts_job_is_gated_on_config(): void
    {
        config(['workride.driver_prompts.enabled' => false]);

        $this->junction();
        $this->checkIn(9.05, 7.45, 12);
        $this->driver();

        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00'));

        (new CalculateDriverPromptsJob)->handle(app(DriverPromptService::class));

        $this->assertDatabaseCount('driver_prompts', 0);
    }
}
