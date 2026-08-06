<?php

namespace Tests\Feature;

use App\Enums\AssetAcquisitionType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Asset;
use App\Models\ChargingStation;
use App\Models\LeaseAgreement;
use App\Models\User;
use App\Services\FleetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverFleetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.fleet.enabled' => true]);
    }

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

    public function test_guest_is_redirected_away_from_fleet(): void
    {
        $this->get('/fleet')->assertRedirect('/login');
    }

    public function test_feature_off_shows_region_notice(): void
    {
        config(['workride.fleet.enabled' => false]);

        $this->actingAs($this->driver())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('Fleet is not enabled in this region yet');
    }

    public function test_driver_without_asset_sees_empty_state(): void
    {
        $this->actingAs($this->driver())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('No bus assigned to you yet');
    }

    public function test_driver_sees_assigned_asset_with_status(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->actingAs($driver)
            ->get('/fleet')
            ->assertOk()
            ->assertSee($asset->plate_number)
            ->assertSee('Pre-trip inspection')
            ->assertSee('Not inspected');
    }

    public function test_passing_inspection_clears_asset_and_shows_on_page(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->actingAs($driver)
            ->post("/fleet/{$asset->id}/inspect", [
                'is_passed' => 1,
                'oil_level' => 'Full',
                'notes' => 'All good',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('inspections', [
            'asset_id' => $asset->id,
            'is_passed' => 1,
            'oil_level' => 'Full',
        ]);

        $this->actingAs($driver)
            ->get('/fleet')
            ->assertSee('Cleared today');
    }

    public function test_failed_inspection_opens_fault_and_shows_grounded(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->actingAs($driver)
            ->post("/fleet/{$asset->id}/inspect", [
                'is_passed' => 0,
                'notes' => 'Blown rear tyre',
            ])
            ->assertRedirect();

        $this->assertSame(1, $asset->faults()->count());
        $this->assertSame('open', $asset->faults()->first()->status->value);

        $this->actingAs($driver)
            ->get('/fleet')
            ->assertSee('Failed today');
    }

    public function test_cannot_inspect_an_asset_assigned_to_someone_else(): void
    {
        $driver = $this->driver();
        $other = $this->driver();
        $asset = $this->asset($other);

        $this->actingAs($driver)
            ->post("/fleet/{$asset->id}/inspect", ['is_passed' => 1])
            ->assertForbidden();

        $this->assertDatabaseCount('inspections', 0);
    }

    public function test_driver_can_report_a_fault(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->actingAs($driver)
            ->post("/fleet/{$asset->id}/faults", [
                'description' => 'Engine knocking at idle',
                'severity' => 4,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('faults', [
            'asset_id' => $asset->id,
            'reported_by' => $driver->id,
            'description' => 'Engine knocking at idle',
            'severity' => 4,
        ]);
    }

    public function test_fault_report_requires_description_and_severity(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->actingAs($driver)
            ->post("/fleet/{$asset->id}/faults", [])
            ->assertSessionHasErrors(['description', 'severity']);
    }

    public function test_api_telemetry_accepts_assigned_driver_and_updates_mileage(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/fleet/{$asset->id}/telemetry", [
                'lat' => 9.05,
                'lng' => 7.45,
                'speed' => 42.5,
                'fuel_level' => 68,
                'mileage' => 12600,
                'harsh_braking' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('mileage', 12600);

        $this->assertSame(12600, $asset->fresh()->mileage);
        $this->assertSame(1, $asset->telemetry()->count());
        $this->assertSame(1, $asset->maintenanceSchedules()->count());
    }

    public function test_api_telemetry_rejects_foreign_asset(): void
    {
        $driver = $this->driver();
        $other = $this->driver();
        $asset = $this->asset($other);

        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/fleet/{$asset->id}/telemetry", ['mileage' => 13000])
            ->assertStatus(422);

        $this->assertDatabaseCount('telemetry', 0);
    }

    public function test_trip_create_shows_fleet_gate_status(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);

        $this->actingAs($driver)
            ->get('/trips/create')
            ->assertOk()
            ->assertSee($asset->plate_number)
            ->assertSee('not inspected today');

        app(FleetService::class)->recordInspection($driver, $asset, ['is_passed' => true]);

        $this->actingAs($driver)
            ->get('/trips/create')
            ->assertOk()
            ->assertSee('cleared to publish');
    }

    public function test_api_telemetry_accepts_ev_battery_fields(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver, AssetStatus::Active);

        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/fleet/{$asset->id}/telemetry", [
                'speed' => 30,
                'battery_soc' => 84.5,
                'range_km' => 187.2,
                'mileage' => 12600,
            ])
            ->assertCreated();

        $sample = $asset->telemetry()->first();
        $this->assertSame('84.50', $sample->battery_soc);
        $this->assertSame('187.20', $sample->range_km);
    }

    public function test_ev_lease_agreement_and_charging_station_roundtrip(): void
    {
        $driver = $this->driver();
        $asset = $this->asset($driver);
        $asset->update(['propulsion' => 'ev']);

        $lease = LeaseAgreement::create([
            'asset_id' => $asset->id,
            'driver_id' => $driver->id,
            'total_ngn' => 8500000,
            'paid_ngn' => 1200000,
            'per_km_ngn' => 45,
            'fuel_baseline_ngn_per_litre' => 1200,
            'status' => 'active',
            'next_due_at' => today()->addMonth(),
        ]);

        $this->assertSame('ev', $asset->fresh()->propulsion->value);
        $this->assertSame(7300000.0, $lease->outstanding());
        $this->assertSame(14, $lease->progressPercent());
        $this->assertFalse($lease->status->isSettled());
        $this->assertSame($asset->id, $lease->asset->id);
        $this->assertSame($driver->id, $lease->driver->id);

        $station = ChargingStation::create([
            'name' => 'Kubwa Fast Charge',
            'lat' => 9.08,
            'lng' => 7.35,
            'kw' => 120,
            'slots' => 4,
            'is_available' => true,
            'corridor' => 'kubwa_cbd',
        ]);

        $this->assertDatabaseHas('charging_stations', ['name' => 'Kubwa Fast Charge', 'is_available' => 1]);
        $this->assertSame(4, $station->slots);
        $this->assertTrue($station->is_available);
    }
}
