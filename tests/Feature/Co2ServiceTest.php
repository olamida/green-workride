<?php

namespace Tests\Feature;

use App\Services\Co2Service;
use Tests\TestCase;

class Co2ServiceTest extends TestCase
{
    private function service(): Co2Service
    {
        return $this->app->make(Co2Service::class);
    }

    public function test_single_occupant_saves_nothing(): void
    {
        $this->assertSame(0.0, $this->service()->co2Kg(1, 22));
        $this->assertSame(0.0, $this->service()->fuelLitres(1, 22));
    }

    public function test_two_occupants_save_co2_per_guide_formula(): void
    {
        // (occupants - 1) * distance * 0.12 → 1 * 22 * 0.12 = 2.64 kg.
        $this->assertSame(2.64, $this->service()->co2Kg(2, 22));
    }

    public function test_trees_equivalent_uses_config_factor(): void
    {
        // 2.64 kg / 21 kg per tree = 0.13 trees.
        $this->assertSame(0.13, $this->service()->treesEquivalent(2.64));
    }

    public function test_fuel_saved_litres(): void
    {
        // distance * 0.10 L/km * occupants → 22 * 0.10 * 2 = 4.4 L.
        $this->assertSame(4.4, $this->service()->fuelLitres(2, 22));
    }

    public function test_for_ride_returns_full_snapshot(): void
    {
        $snapshot = $this->service()->forRide(2, 22);

        $this->assertSame(2.64, $snapshot['co2_kg']);
        $this->assertSame(0.13, $snapshot['trees']);
        $this->assertSame(4.4, $snapshot['fuel_litres']);
    }

    public function test_distance_km_uses_haversine(): void
    {
        // Kubwa Junction (9.07, 7.45) → Federal Secretariat (9.045, 7.492) ≈ 6 km.
        $distance = $this->service()->distanceKm(9.07, 7.45, 9.045, 7.492);

        $this->assertGreaterThan(4.0, $distance);
        $this->assertLessThan(8.0, $distance);
    }
}
