<?php

namespace Tests\Feature;

use App\Enums\BusScheduleStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\BusSchedule;
use App\Models\GtfsRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.scheduling.enabled' => true]);
    }

    private function schedule(?Corridor $corridor = null): BusSchedule
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        $vehicle = Vehicle::factory()->create(['user_id' => $driver->id]);

        $route = GtfsRoute::factory()
            ->forCorridor($corridor ?? Corridor::KubwaCbd)
            ->create();

        return BusSchedule::factory()->create([
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'departure_time' => '06:30',
            'end_time' => '07:00',
            'frequency_minutes' => 15,
            'days_of_week' => ['mon'],
        ]);
    }

    public function test_materialize_day_creates_trip_rows_for_each_departure_slot(): void
    {
        $schedule = $this->schedule();

        // Debug: check the route and corridor
        $route = $schedule->route;
        dump('Route ID: '.($route?->id ?? 'null'));
        dump('Route Corridor: '.($route?->corridor ?? 'null'));
        dump('Schedule Corridor: '.$schedule->corridor()->value);
        dump('Schedule runsOn mon: '.($schedule->runsOn('mon') ? 'true' : 'false'));
        dump('Schedule departureTimes: '.json_encode($schedule->departureTimes()));

        $service = app(SchedulingService::class);

        // Use a future Monday to avoid "isPast" check
        $monday = now()->startOfWeek()->addWeek();
        $created = $service->materializeDay($monday);

        dump('Created: '.$created);
        dump('Trips count: '.Trip::where('schedule_ref', 'LIKE', 'SCHED-'.$schedule->id.'-%')->count());

        $this->assertSame(3, $created);
        $this->assertSame(3, Trip::where('schedule_ref', 'LIKE', 'SCHED-'.$schedule->id.'-%')->count());

        $first = Trip::where('schedule_ref', 'SCHED-'.$schedule->id.'-'.$monday->format('Y-m-d').'-0630')->first();
        $this->assertNotNull($first);
        $this->assertSame($schedule->corridor(), $first->corridor);
        $this->assertSame($schedule->driver_id, $first->driver_id);
        $this->assertSame($schedule->vehicle_id, $first->vehicle_id);
        $this->assertSame(TripStatus::Scheduled, $first->status);
        $this->assertSame($monday->format('Y-m-d').' 06:30:00', $first->departure_time->format('Y-m-d H:i:s'));
        $this->assertSame(2, $first->waypoints()->count());
    }

    public function test_materialize_day_is_idempotent(): void
    {
        $schedule = $this->schedule();
        $service = app(SchedulingService::class);

        $monday = now()->startOfWeek()->addWeek();
        $service->materializeDay($monday);
        $second = $service->materializeDay($monday);
        $third = $service->materializeDay($monday);

        $this->assertSame(0, $second + $third);
        $this->assertSame(3, Trip::count());
    }

    public function test_materialize_day_skips_past_departures(): void
    {
        $monday = now()->startOfWeek()->addWeek();
        $this->travelTo($monday->copy()->setTime(6, 45, 0));

        $schedule = $this->schedule();
        $service = app(SchedulingService::class);

        // 06:30 is already past; 06:45 and 07:00 remain.
        $created = $service->materializeDay($monday);

        $this->assertSame(2, $created);
        $this->assertNull(Trip::where('schedule_ref', 'SCHED-'.$schedule->id.'-'.$monday->format('Y-m-d').'-0630')->first());
    }

    public function test_materialize_day_skips_days_not_in_schedule(): void
    {
        $this->schedule(); // mon only

        // Next Tuesday.
        $tuesday = now()->startOfWeek()->addWeek()->addDay();
        $this->assertSame(0, app(SchedulingService::class)->materializeDay($tuesday));
        $this->assertSame(0, Trip::count());
    }

    public function test_materialize_day_skips_paused_schedules(): void
    {
        $schedule = $this->schedule();
        $schedule->update(['status' => BusScheduleStatus::Paused]);

        $monday = now()->startOfWeek()->addWeek();
        $this->assertSame(0, app(SchedulingService::class)->materializeDay($monday));
        $this->assertSame(0, Trip::count());
    }

    public function test_materialize_day_is_disabled_when_feature_gate_is_off(): void
    {
        config(['workride.scheduling.enabled' => false]);
        $this->schedule();

        $monday = now()->startOfWeek()->addWeek();
        $this->assertSame(0, app(SchedulingService::class)->materializeDay($monday));
        $this->assertSame(0, Trip::count());
    }

    public function test_next_departures_merges_materialised_trips_and_future_slots(): void
    {
        $monday = now()->startOfWeek()->addWeek();
        $this->travelTo($monday->copy()->setTime(5, 0, 0));

        $schedule = $this->schedule();
        $service = app(SchedulingService::class);

        $service->materializeDay($monday);

        $departures = $service->nextDepartures();

        // 3 materialised (06:30, 06:45, 07:00) — all still future from 05:00.
        $this->assertCount(3, $departures);
        $this->assertSame('trip', $departures[0]['source']);
        $this->assertNotNull($departures[0]['trip_id']);
        $this->assertSame($schedule->id, $departures[0]['schedule_id']);
    }

    public function test_next_departures_filters_by_corridor(): void
    {
        $monday = now()->startOfWeek()->addWeek();
        $this->travelTo($monday->copy()->setTime(5, 0, 0));

        $this->schedule(); // Kubwa

        $departures = app(SchedulingService::class)->nextDepartures(Corridor::NyanyaIdu);

        $this->assertSame([], $departures);
    }

    public function test_next_departures_returns_empty_when_disabled(): void
    {
        config(['workride.scheduling.enabled' => false]);

        $this->assertSame([], app(SchedulingService::class)->nextDepartures());
    }

    public function test_departure_times_respect_frequency_window(): void
    {
        $schedule = $this->schedule();

        $this->assertSame(['06:30', '06:45', '07:00'], $schedule->departureTimes());
    }

    public function test_single_departure_when_end_time_is_null(): void
    {
        $schedule = $this->schedule();
        $schedule->update(['end_time' => null]);

        $this->assertSame(['06:30'], $schedule->departureTimes());
    }

    public function test_reference_for_is_idempotent_and_deterministic(): void
    {
        $schedule = $this->schedule();

        $monday = now()->startOfWeek()->addWeek();
        $this->assertSame(
            'SCHED-'.$schedule->id.'-'.$monday->format('Y-m-d').'-0630',
            $schedule->referenceFor($monday->format('Y-m-d'), '06:30')
        );
    }
}
