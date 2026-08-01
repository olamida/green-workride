<?php

namespace App\Services;

/**
 * Community impact math — the numbers behind every "X kg CO2 saved" badge.
 *
 * Guide formula (Impact Analytics):
 *   saving = (occupants - 1) * distance_km * 0.12 kg
 *   trees  = co2 / 21
 *   fuel   = distance_km / 10 * occupants (approx litres)
 *
 * Every factor is configurable via config/workride.php.
 */
class Co2Service
{
    public function __construct(
        private GeofenceService $geofence,
    ) {}

    /**
     * CO2 saved (kg) when $occupants share a vehicle for $distanceKm.
     */
    public function co2Kg(int $occupants, float $distanceKm): float
    {
        if ($occupants < 2) {
            return 0;
        }

        return round(($occupants - 1) * $distanceKm * (float) config('workride.co2_per_passenger_km', 0.12), 2);
    }

    /**
     * Trees-equivalent for a CO2 saving (kg CO2 → trees planted offset).
     */
    public function treesEquivalent(float $co2Kg): float
    {
        $perTree = (float) config('workride.trees_per_kg_co2', 21);

        return $perTree > 0 ? round($co2Kg / $perTree, 2) : 0;
    }

    /**
     * Fuel saved (litres) when $occupants share a vehicle for $distanceKm.
     */
    public function fuelLitres(int $occupants, float $distanceKm): float
    {
        if ($occupants < 2) {
            return 0;
        }

        return round($distanceKm * (float) config('workride.fuel_litres_per_km', 0.10) * $occupants, 2);
    }

    /**
     * Convenience: one call for the full impact snapshot of a shared ride.
     *
     * @return array{co2_kg: float, trees: float, fuel_litres: float}
     */
    public function forRide(int $occupants, float $distanceKm): array
    {
        $co2 = $this->co2Kg($occupants, $distanceKm);

        return [
            'co2_kg' => $co2,
            'trees' => $this->treesEquivalent($co2),
            'fuel_litres' => $this->fuelLitres($occupants, $distanceKm),
        ];
    }

    /**
     * Straight-line distance between two points (km), reusing the geofence
     * haversine helper. Used when a trip has no recorded route distance.
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return round($this->geofence->haversine($lat1, $lng1, $lat2, $lng2) / 1000, 2);
    }
}
