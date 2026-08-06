<?php

namespace Tests\Feature;

use App\Enums\ForecastEventType;
use App\Services\ForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OpsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_ops_tables_exist(): void
    {
        $tables = [
            'junctions', 'demand_surveys', 'od_surveys', 'od_matrix', 'demand_requests',
            'probe_demand_points', 'unions', 'stakeholder_remittances', 'permits',
            'assets', 'maintenance_schedules', 'inspections', 'faults', 'telemetry',
            'forecasts', 'duty_rosters', 'schedules', 'driver_scores', 'gtfs_validations',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_trips_has_asset_column(): void
    {
        $this->assertTrue(Schema::hasColumn('trips', 'asset_id'));
    }

    public function test_remittance_reference_is_unique(): void
    {
        $unique = collect(Schema::getIndexes('stakeholder_remittances'))
            ->first(fn ($index) => $index['unique']);

        $this->assertNotNull($unique, 'stakeholder_remittances has no unique index');

        $driverScoreUnique = collect(Schema::getIndexes('driver_scores'))
            ->first(fn ($index) => $index['unique'] && count($index['columns']) === 2);

        $this->assertNotNull($driverScoreUnique, 'driver_scores lacks its user+period unique pair');
    }

    public function test_forecast_default_multiplier_map(): void
    {
        $service = app(ForecastService::class);

        $this->assertSame(1.6, $service->defaultMultiplier(ForecastEventType::Govt));
        $this->assertSame(0.7, $service->defaultMultiplier(ForecastEventType::Mosque));
        $this->assertSame(1.4, $service->defaultMultiplier(ForecastEventType::Weather));
    }

    public function test_ev_lease_schema_seams_exist(): void
    {
        $this->assertTrue(Schema::hasTable('lease_agreements'));
        $this->assertTrue(Schema::hasTable('charging_stations'));
        $this->assertTrue(Schema::hasColumn('assets', 'propulsion'));
        $this->assertTrue(Schema::hasColumn('telemetry', 'battery_soc'));
        $this->assertTrue(Schema::hasColumn('telemetry', 'range_km'));

        $leaseColumns = collect(Schema::getColumns('lease_agreements'))->pluck('name')->all();
        foreach (['asset_id', 'driver_id', 'total_ngn', 'paid_ngn', 'per_km_ngn', 'fuel_baseline_ngn_per_litre', 'status', 'next_due_at'] as $column) {
            $this->assertContains($column, $leaseColumns, "Missing lease_agreements column: {$column}");
        }

        $stationColumns = collect(Schema::getColumns('charging_stations'))->pluck('name')->all();
        foreach (['name', 'lat', 'lng', 'kw', 'slots', 'is_available', 'corridor'] as $column) {
            $this->assertContains($column, $stationColumns, "Missing charging_stations column: {$column}");
        }
    }

    public function test_ev_lease_is_feature_gated(): void
    {
        $this->assertFalse(config('workride.ev_lease.enabled'));
    }
}
