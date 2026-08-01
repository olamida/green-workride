<?php

namespace App\Services;

use App\Models\Workplace;

class GeofenceService
{
    /**
     * Is the point inside the FCT bounding box? (Phase 1: bounding box,
     * later replaced with a proper FCT polygon.)
     */
    public function isInsideFct(float $lat, float $lng): bool
    {
        $bounds = config('workride.fct_bounds');

        return $lat >= $bounds['min_lat']
            && $lat <= $bounds['max_lat']
            && $lng >= $bounds['min_lng']
            && $lng <= $bounds['max_lng'];
    }

    public function isInsideWorkplace(Workplace $workplace, float $lat, float $lng): bool
    {
        if (! $workplace->lat || ! $workplace->lng) {
            return false;
        }

        $distance = $this->haversine($workplace->lat, $workplace->lng, $lat, $lng);

        return $distance <= $workplace->geofence_radius_m;
    }

    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // metres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
