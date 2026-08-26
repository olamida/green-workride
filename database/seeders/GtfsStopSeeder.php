<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\GtfsStop;
use Illuminate\Database\Seeder;

/**
 * Seeds the GTFS stops table with representative Abuja stops along the three
 * WorkRide corridors (Kubwa→CBD, Nyanya→Idu, Lugbe→CBD). Coordinates are
 * approximations on the real Abuja road network for the first GTFS feed.
 */
class GtfsStopSeeder extends Seeder
{
    public function run(): void
    {
        $abuja = City::where('slug', 'abuja')->first();

        $stops = [
            // ---- Kubwa → CBD corridor ----
            ['KUB-01', 'Kubwa Junction', 9.1117, 7.3304, 'kubwa_cbd', 'Kubwa'],
            ['KUB-02', 'Kubwa Police Post', 9.1063, 7.3402, 'kubwa_cbd', 'Kubwa'],
            ['KUB-03', 'Kubwa Market', 9.1004, 7.3521, 'kubwa_cbd', 'Kubwa'],
            ['KUB-04', 'Dutse-Alhaji', 9.0822, 7.3907, 'kubwa_cbd', 'Dutse'],
            ['KUB-05', 'Katampe Junction', 9.1154, 7.4012, 'kubwa_cbd', 'Katampe'],
            ['KUB-06', 'Berger Junction (Kubwa Axis)', 9.0764, 7.4186, 'kubwa_cbd', 'Berger'],
            ['KUB-07', 'NNPC Mega Station', 9.0695, 7.4403, 'kubwa_cbd', 'Kubwa Road'],
            ['KUB-08', 'Dei-Dei Junction', 9.0601, 7.4490, 'kubwa_cbd', 'Dei-Dei'],
            ['KUB-09', 'Banex Junction', 9.0729, 7.4929, 'kubwa_cbd', 'Wuse 2'],
            ['KUB-10', 'Wuse Market', 9.0778, 7.4861, 'kubwa_cbd', 'Wuse'],
            ['KUB-11', 'Wuse Zone 1', 9.0833, 7.4722, 'kubwa_cbd', 'Wuse'],
            ['KUB-12', 'Maitama Roundabout', 9.0900, 7.5000, 'kubwa_cbd', 'Maitama'],
            ['KUB-13', 'Area 1', 9.0639, 7.4706, 'kubwa_cbd', 'Garki'],
            ['KUB-14', 'Area 2', 9.0606, 7.4558, 'kubwa_cbd', 'Garki'],
            ['KUB-15', 'Area 3', 9.0572, 7.4417, 'kubwa_cbd', 'Garki'],
            ['KUB-16', 'Federal Secretariat Gate', 9.0500, 7.4900, 'kubwa_cbd', 'Central Business District'],
            ['KUB-17', 'Central Business District', 9.0450, 7.4922, 'kubwa_cbd', 'Central Business District'],
            ['KUB-18', 'Shehu Shagari Way', 9.0489, 7.4844, 'kubwa_cbd', 'Central Business District'],
            ['KUB-19', 'Old Federal Secretariat', 9.0544, 7.5014, 'kubwa_cbd', 'Central Business District'],
            ['KUB-20', 'Garki II', 9.0400, 7.4700, 'kubwa_cbd', 'Garki'],

            // ---- Nyanya → Idu corridor ----
            ['NYY-01', 'Nyanya Under-Bridge', 9.0019, 7.5453, 'nyanya_idu', 'Nyanya'],
            ['NYY-02', 'Nyanya Bus Stop', 8.9994, 7.5494, 'nyanya_idu', 'Nyanya'],
            ['NYY-03', 'Karu Market', 8.9960, 7.5640, 'nyanya_idu', 'Karu'],
            ['NYY-04', 'Karu Junction', 8.9941, 7.5721, 'nyanya_idu', 'Karu'],
            ['NYY-05', 'Jikwoyi Junction', 9.0050, 7.5600, 'nyanya_idu', 'Jikwoyi'],
            ['NYY-06', 'Kurudu Junction', 9.0080, 7.5410, 'nyanya_idu', 'Kurudu'],
            ['NYY-07', 'Kugbo Junction', 9.0000, 7.5370, 'nyanya_idu', 'Kugbo'],
            ['NYY-08', 'Apo Legislative Quarters', 8.9950, 7.5080, 'nyanya_idu', 'Apo'],
            ['NYY-09', 'Gudu', 9.0060, 7.5020, 'nyanya_idu', 'Gudu'],
            ['NYY-10', 'Gaduwa', 9.0010, 7.4940, 'nyanya_idu', 'Gaduwa'],
            ['NYY-11', 'Area 11 Junction', 9.0297, 7.4247, 'nyanya_idu', 'Garki'],
            ['NYY-12', 'Wuse Zone 4', 9.0771, 7.4567, 'nyanya_idu', 'Wuse'],
            ['NYY-13', 'Old Parade Ground', 9.0510, 7.4680, 'nyanya_idu', 'Garki'],
            ['NYY-14', 'Idu Industrial Layout', 8.9890, 7.3380, 'nyanya_idu', 'Idu'],
            ['NYY-15', 'Idu Railway Station', 8.9850, 7.3300, 'nyanya_idu', 'Idu'],
            ['NYY-16', 'Idu Interchange', 8.9920, 7.3460, 'nyanya_idu', 'Idu'],
            ['NYY-17', 'Kukwaba', 8.9870, 7.4400, 'nyanya_idu', 'Kukwaba'],
            ['NYY-18', 'Cement Company', 8.9800, 7.4100, 'nyanya_idu', 'Kukwaba'],

            // ---- Lugbe → CBD corridor ----
            ['LUG-01', 'Lugbe Junction', 9.0050, 7.3600, 'lugbe_cbd', 'Lugbe'],
            ['LUG-02', 'Lugbe Market', 9.0000, 7.3500, 'lugbe_cbd', 'Lugbe'],
            ['LUG-03', 'Airport Road (Lugbe)', 9.0100, 7.3700, 'lugbe_cbd', 'Lugbe'],
            ['LUG-04', 'Gosa Junction', 9.0150, 7.3900, 'lugbe_cbd', 'Gosa'],
            ['LUG-05', 'Kuchingoro Junction', 9.0200, 7.4100, 'lugbe_cbd', 'Kuchingoro'],
            ['LUG-06', 'Dutse Alhaji Junction', 9.0300, 7.4300, 'lugbe_cbd', 'Dutse'],
            ['LUG-07', 'Gwarinpa Estate Gate', 9.0900, 7.4300, 'lugbe_cbd', 'Gwarinpa'],
            ['LUG-08', 'Life Camp Junction', 9.0700, 7.4600, 'lugbe_cbd', 'Life Camp'],
            ['LUG-09', 'Area 2 (Lugbe Axis)', 9.0606, 7.4558, 'lugbe_cbd', 'Garki'],
            ['LUG-10', 'Area 3 (Lugbe Axis)', 9.0572, 7.4417, 'lugbe_cbd', 'Garki'],
            ['LUG-11', 'Sheraton Junction', 9.0264, 7.4475, 'lugbe_cbd', 'Garki'],
            ['LUG-12', 'Area 10', 9.0322, 7.4478, 'lugbe_cbd', 'Garki'],
            ['LUG-13', 'Eagle Square', 9.0400, 7.4850, 'lugbe_cbd', 'Central Business District'],
            ['LUG-14', 'Federal Secretariat Gate', 9.0500, 7.4900, 'lugbe_cbd', 'Central Business District'],
            ['LUG-15', 'Central Business District', 9.0450, 7.4922, 'lugbe_cbd', 'Central Business District'],
        ];

        foreach ($stops as [$stopId, $name, $lat, $lng, $corridor, $zone]) {
            GtfsStop::updateOrCreate(
                ['stop_id' => $stopId],
                [
                    'stop_name' => $name,
                    'stop_lat' => $lat,
                    'stop_lon' => $lng,
                    'corridor' => $corridor,
                    'zone' => $zone,
                    'city_id' => $abuja?->id,
                ],
            );
        }
    }
}
