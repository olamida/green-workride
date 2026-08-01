<?php

namespace App\Services;

use App\Enums\Corridor;

class PricingService
{
    /**
     * Fixed per-corridor fare — anti-surge.
     * Volunteer rides are always free.
     */
    public function fareFor(Corridor $corridor, bool $isFreeVolunteer = false): float
    {
        if ($isFreeVolunteer) {
            return 0.0;
        }

        return (float) (config("workride.max_fare_per_corridor.{$corridor->value}") ?? 800);
    }

    public function commission(float $fare): float
    {
        return round($fare * (float) config('workride.commission_rate'), 2);
    }

    public function unionFee(float $fare): float
    {
        return round($fare * (float) config('workride.union_fee_rate'), 2);
    }

    public function driverEarning(float $fare): float
    {
        $insurance = (float) config('workride.insurance_per_trip');

        return max(0.0, round($fare - $this->commission($fare) - $this->unionFee($fare) - $insurance, 2));
    }
}
