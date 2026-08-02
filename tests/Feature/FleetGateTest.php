<?php

namespace Tests\Feature;

use App\Enums\AssetAcquisitionType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\MaintenanceType;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Asset;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FleetService;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FleetGateTest extends TestCase
{
    use RefreshDatabase;

    private function driver(): User
    {
        return User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    private function asset(User $driver, AssetStatus $status = AssetStatus::Active): Asset
    {
        return Asset::create([
            'asset_type' => AssetType::Bus,
            'acquisition_type' => AssetAcquisitionType::Lease,
            'make' => 'Toyota',
            'model' => 'Coaster',
            'plate_number' => strtoupper(fake()->unique()->regexify('[A-Z]{3}-\d{3}[A-Z]{2}')),
            'purchase_cost' => 0,
            'lease_monthly' => 850000,
            'mileage' => 12400,
            'status' => $status,
            'assigned_driver_id' => $driver->id,
            'corridor' => 'kubwa_cbd',
        ]);
    }

    public function test_no_asset_means_no_gate(): void
    {
        $driver = $this->driver();

        $this->assertNull(app(FleetService::class)->assertPublishable($driver));
    }

    public function test_active_assigned_asset_passes_the_gate(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->assertSame($asset->id, app(FleetService::class)->assertPublishable($driver)->id);
    }

    public function test_grounded_asset_blocks_publishing(): void
    {
        $driver = $this->driver();
        $this->asset($driver, AssetStatus::Grounded);

        $this->expectException(ValidationException::class);

        app(FleetService::class)->assertPublishable($driver);
    }

    public function test_failed_inspection_today_blocks_until_passing(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        app(FleetService::class)->recordInspection($driver, $asset, ['is_passed' => false, 'notes' => 'Blown tyre']);

        try {
            app(FleetService::class)->assertPublishable($driver);
            $this->fail('Expected a failed-inspection block.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('failed its pre-trip inspection', $e->getMessage());
        }

        app(FleetService::class)->recordInspection($driver, $asset, ['is_passed' => true]);

        $this->assertSame($asset->id, app(FleetService::class)->assertPublishable($driver)->id);
    }

    public function test_failed_inspection_opens_a_fault_ticket(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $inspection = app(FleetService::class)->recordInspection($driver, $asset, ['is_passed' => false, 'notes' => 'Brake light out']);

        $this->assertFalse($inspection->is_passed);
        $this->assertSame(1, $asset->faults()->count());
        $this->assertSame('open', $asset->faults()->first()->status->value);
    }

    public function test_fault_resolution_clears_the_asset(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $fault = app(FleetService::class)->recordFault($driver, $asset, ['description' => 'Engine knock', 'severity' => 5]);
        $resolved = app(FleetService::class)->resolveFault($fault, $driver->id, 'Replaced mount.');

        $this->assertSame('fixed', $resolved->status->value);
        $this->assertNotNull($resolved->resolved_at);
    }

    public function test_preventive_maintenance_schedule_gets_a_due_date(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $schedule = app(FleetService::class)->scheduleMaintenance($asset, MaintenanceType::Preventive5000km);

        $this->assertNotNull($schedule->due_date);
        $this->assertSame($asset->mileage + 5000, $schedule->due_km);
        $this->assertSame('scheduled', $schedule->status->value);
    }

    public function test_telemetry_updates_mileage_and_queues_preventive(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        app(FleetService::class)->recordTelemetry($asset, ['mileage' => 20000, 'speed' => 40, 'harsh_braking' => true]);

        $this->assertSame(20000, $asset->fresh()->mileage);
        $this->assertSame(1, $asset->maintenanceSchedules()->where('type', MaintenanceType::Preventive5000km->value)->count());
        $this->assertSame(1, $asset->telemetry()->count());
    }

    public function test_trip_publish_records_the_asset_id(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);
        $vehicle = $driver->vehicles()->first() ?? Vehicle::factory()->create(['user_id' => $driver->id]);

        $trip = app(TripService::class)->publish($driver, [
            'vehicle_id' => $vehicle->id,
            'asset_id' => $asset->id,
            'corridor' => 'kubwa_cbd',
            'origin_text' => 'Kubwa Junction',
            'destination_text' => 'Federal Secretariat',
            'current_lat' => 9.05,
            'current_lng' => 7.45,
            'total_seats' => 4,
            'departure_time' => now()->addHour(),
            'is_free_volunteer' => false,
        ]);

        $this->assertSame($asset->id, $trip->asset_id);
        $this->assertSame(TripStatus::Scheduled, $trip->status);
    }

    public function test_trip_publish_blocks_on_foreign_asset(): void
    {
        $driver = $this->driver();
        $other = $this->driver();
        $vehicle = $driver->vehicles()->first() ?? Vehicle::factory()->create(['user_id' => $driver->id]);
        $asset = $this->asset($other);

        $this->expectException(ValidationException::class);

        app(TripService::class)->publish($driver, [
            'vehicle_id' => $vehicle->id,
            'asset_id' => $asset->id,
            'corridor' => 'kubwa_cbd',
            'origin_text' => 'Kubwa Junction',
            'destination_text' => 'Federal Secretariat',
            'total_seats' => 4,
            'departure_time' => now()->addHour(),
            'is_free_volunteer' => false,
        ]);
    }
}
