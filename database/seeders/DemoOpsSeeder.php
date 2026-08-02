<?php

namespace Database\Seeders;

use App\Enums\AssetAcquisitionType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\ForecastEventType;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\DutyRoster;
use App\Models\Forecast;
use App\Models\Junction;
use App\Models\Union;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * v3.0/v4.0 operations demo data (guide §9/§9B/§10/§11): the demand-research
 * junction catalog, NURTW/RTEAN chapters, one leased fleet asset + maintenance
 * cadence, and an event-aware demand calendar. No-ops unless a gate is on —
 * the tables themselves are always available for real operations.
 */
class DemoOpsSeeder extends Seeder
{
    public function run(): void
    {
        $demandOn = (bool) config('workride.demand.enabled', false);
        $fleetOn = (bool) config('workride.fleet.enabled', false);
        $stakeholdersOn = (bool) config('workride.stakeholders.enabled', false);
        $forecastsOn = (bool) config('workride.forecasts.enabled', false);

        if ($demandOn) {
            $this->seedJunctions();
        }

        if ($stakeholdersOn) {
            $this->seedUnions();
        }

        if ($fleetOn) {
            $this->seedFleet();
        }

        if ($forecastsOn) {
            $this->seedForecasts();
        }

        if ($fleetOn || $demandOn) {
            $this->seedRosterAndScores();
        }
    }

    private function seedJunctions(): void
    {
        $junctions = [
            ['Kubwa Junction', 'kubwa_cbd', 9.0794, 7.2950, 'Bwari', 'Morning peak: 6-8am, 70% to CBD'],
            ['Berger Junction', 'nyanya_idu', 9.0300, 7.4330, 'FCT-Keffi corridor', 'Evening peak: 4-7pm, 80% to CBD'],
            ['Banex Plaza Junction', 'kubwa_cbd', 9.0581, 7.4890, 'Wuse', 'All day, high churn'],
            ['Nyanya Under-Bridge', 'nyanya_idu', 8.9985, 7.4640, 'Karu', 'Morning peak 5:30-8am'],
            ['Lugbe Junction', 'lugbe_cbd', 8.9850, 7.3550, 'Airport road', 'Weekday 6-9am + 4-7pm'],
        ];

        foreach ($junctions as [$name, $corridor, $lat, $lng, $zone, $notes]) {
            Junction::updateOrCreate(
                ['name' => $name],
                ['corridor' => $corridor, 'lat' => $lat, 'lng' => $lng, 'zone' => $zone, 'is_active' => true, 'notes' => $notes]
            );
        }
    }

    private function seedUnions(): void
    {
        $unions = [
            ['NURTW Kubwa Park', 'Kubwa Park, Kubwa', 9.0794, 7.2950, 'kubwa_cbd', 'Alhaji Musa', '08030000001'],
            ['NURTW Berger Park', 'Berger Park, Nyanya', 9.0300, 7.4330, 'nyanya_idu', 'Chief Emeka', '08030000002'],
            ['RTEAN Lugbe Chapter', 'Lugbe Park, Airport Road', 8.9850, 7.3550, 'lugbe_cbd', 'Mrs Bola', '08030000003'],
        ];

        foreach ($unions as [$name, $park, $lat, $lng, $corridor, $contact, $phone]) {
            Union::updateOrCreate(
                ['name' => $name],
                ['park_location' => $park, 'lat' => $lat, 'lng' => $lng, 'corridor' => $corridor, 'commission_rate' => 0.05, 'contact_name' => $contact, 'contact_phone' => $phone, 'is_active' => true]
            );
        }
    }

    private function seedFleet(): void
    {
        $driver = User::where('email', 'driver@workride.ng')->first();

        $asset = Asset::updateOrCreate(
            ['plate_number' => 'ABJ-849-KJ'],
            [
                'asset_type' => AssetType::Bus,
                'acquisition_type' => AssetAcquisitionType::Lease,
                'vin' => 'JTFST22P400123456',
                'make' => 'Toyota',
                'model' => 'Coaster 18-seater',
                'year' => 2021,
                'purchase_cost' => 0,
                'lease_monthly' => 850000.00,
                'depreciation_rate' => 10,
                'mileage' => 12400,
                'status' => AssetStatus::Active,
                'assigned_driver_id' => $driver?->id,
                'corridor' => 'kubwa_cbd',
                'notes' => 'Agofure lease — lease-to-own pilot, Kubwa-CBD corridor.',
            ]
        );

        if (! $asset->maintenanceSchedules()->exists()) {
            $asset->maintenanceSchedules()->create([
                'type' => MaintenanceType::MonthlyInspection,
                'due_date' => now()->startOfMonth()->addMonth(),
                'status' => 'scheduled',
                'notes' => 'Lease-required monthly inspection.',
            ]);
        }
    }

    private function seedForecasts(): void
    {
        $admin = User::where('email', config('workride.admin.email'))->first();

        $events = [
            [now()->addDays(3)->toDateString(), ForecastEventType::Govt, 'Salary week — FAAC payment', 'kubwa_cbd', 1.6, 3, 'Staff +60% demand 25th-5th. Add buses.'],
            [now()->addDays(4)->toDateString(), ForecastEventType::Mosque, 'Friday Juma\'a', 'kubwa_cbd', 0.7, 0, 'Reduce CBD trips after 2:30pm; mosque corridors only.'],
        ];

        foreach ($events as [$date, $type, $name, $corridor, $multiplier, $extra, $notes]) {
            Forecast::updateOrCreate(
                ['date' => $date, 'event_name' => $name],
                [
                    'event_type' => $type,
                    'corridor' => $corridor,
                    'expected_demand_multiplier' => $multiplier,
                    'recommended_extra_vehicles' => $extra,
                    'notes' => $notes,
                    'created_by' => $admin?->id,
                ]
            );
        }
    }

    private function seedRosterAndScores(): void
    {
        $driver = User::where('email', 'driver@workride.ng')->first();
        $admin = User::where('email', config('workride.admin.email'))->first();

        if ($driver) {
            $roster = DutyRoster::updateOrCreate(
                ['name' => 'Kubwa-CBD morning peak', 'date' => today()->toDateString()],
                ['corridor' => 'kubwa_cbd', 'status' => 'published', 'notes' => 'Fleet pilot — 2 scheduled runs.', 'created_by' => $admin?->id]
            );

            if (! $roster->schedules()->exists()) {
                $roster->schedules()->create([
                    'driver_id' => $driver->id,
                    'corridor' => 'kubwa_cbd',
                    'starts_at' => today()->setTime(6, 30),
                    'ends_at' => today()->setTime(7, 30),
                    'status' => 'scheduled',
                    'notes' => 'Run 1 — Kubwa Junction to Federal Secretariat.',
                ]);
            }
        }
    }
}
