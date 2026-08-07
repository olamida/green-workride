<?php

namespace Tests\Feature;

use App\Enums\BusScheduleStatus;
use App\Enums\Corridor;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\BusSchedule;
use App\Models\GtfsRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSchedulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.scheduling.enabled' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    private function passenger(): User
    {
        return User::factory()->create([
            'role' => UserRole::Passenger,
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);
    }

    private function schedule(): BusSchedule
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
        $vehicle = Vehicle::factory()->create(['user_id' => $driver->id]);

        return BusSchedule::factory()->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_guest_is_redirected_away_from_schedules(): void
    {
        $this->get(route('admin.schedules.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_schedules(): void
    {
        $this->actingAs($this->passenger())
            ->get(route('admin.schedules.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_schedules_index(): void
    {
        $schedule = $this->schedule();

        $this->actingAs($this->admin())
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee('Recurring Schedules')
            ->assertSee($schedule->routeLabel());
    }

    public function test_admin_can_view_create_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.schedules.create'))
            ->assertOk()
            ->assertSee('Create schedule');
    }

    public function test_admin_can_create_a_schedule(): void
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
        $vehicle = Vehicle::factory()->create(['user_id' => $driver->id]);
        $route = GtfsRoute::factory()->forCorridor(Corridor::KubwaCbd)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.schedules.store'), [
                'route_id' => $route->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'departure_time' => '06:30',
                'end_time' => '09:00',
                'frequency_minutes' => 15,
                'days_of_week' => ['mon', 'fri'],
            ])
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertDatabaseHas('bus_schedules', [
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'departure_time' => '06:30',
            'end_time' => '09:00',
            'frequency_minutes' => 15,
        ]);
    }

    public function test_admin_create_schedule_requires_valid_days(): void
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
        $vehicle = Vehicle::factory()->create(['user_id' => $driver->id]);
        $route = GtfsRoute::factory()->forCorridor(Corridor::KubwaCbd)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.schedules.store'), [
                'route_id' => $route->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'departure_time' => '06:30',
                'frequency_minutes' => 15,
                'days_of_week' => ['funday'],
            ])
            ->assertSessionHasErrors('days_of_week.0');
    }

    public function test_admin_can_toggle_a_schedule(): void
    {
        $schedule = $this->schedule();

        $this->actingAs($this->admin())
            ->post(route('admin.schedules.toggle', $schedule))
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertSame(BusScheduleStatus::Paused, $schedule->fresh()->status);

        $this->actingAs($this->admin())
            ->post(route('admin.schedules.toggle', $schedule))
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertSame(BusScheduleStatus::Active, $schedule->fresh()->status);
    }

    public function test_admin_can_materialise_a_schedule(): void
    {
        $this->travelTo('2026-08-10 05:00:00'); // Monday

        $schedule = $this->schedule();
        $schedule->update([
            'departure_time' => '06:30',
            'end_time' => '07:00',
            'frequency_minutes' => 15,
            'days_of_week' => ['mon'],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.schedules.materialize', $schedule))
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertSame(3, Trip::count());
    }

    public function test_admin_can_delete_a_schedule(): void
    {
        $schedule = $this->schedule();

        $this->actingAs($this->admin())
            ->delete(route('admin.schedules.destroy', $schedule))
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertDatabaseMissing('bus_schedules', ['id' => $schedule->id]);
    }
}
