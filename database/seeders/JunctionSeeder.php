<?php

namespace Database\Seeders;

use App\Models\Junction;
use Illuminate\Database\Seeder;

/**
 * Seeds the junction catalog — the real Abuja high-traffic passenger hotspots
 * where thousands wait daily (5:30-9am and 4-8pm) for demand surveys, GTFS
 * stops and trip waypoints. Every coordinate is on the real road network.
 *
 * Rows carry the demand-intel columns (passenger_volume_daily, is_major_hub,
 * state, avg_wait_time_mins) from the 45-junction v6 seed (gallery
 * WORKRIDE-45-JUNCTIONS-SEED.sql), mapped onto the actual `junctions` schema.
 * The garki_wuse CBD-only junctions are carried on the kubwa_cbd corridor (the
 * Corridor enum has no garki_wuse case); slug/union_id/photo_path are deferred.
 */
class JunctionSeeder extends Seeder
{
    // [name, corridor, lat, lng, zone, volume_daily, is_major_hub, state, wait_mins, notes]
    private const JUNCTIONS = [
        // ---- A. Kubwa Axis (Kubwa → CBD corridor — primary, heaviest) ----
        ['Kubwa Junction', 'kubwa_cbd', 9.1500, 7.3333, 'Kubwa', 2500, true, 'FCT', 25, '2000+ passengers daily. Main Kubwa terminal.'],
        ['Kubwa FHA Junction', 'kubwa_cbd', 9.1650, 7.3300, 'Kubwa', 1200, false, 'FCT', 20, 'FHA Kubwa estate exit.'],
        ['Kubwa Second Gate', 'kubwa_cbd', 9.1550, 7.3400, 'Kubwa', 1000, false, 'FCT', 18, '2nd Gate estate exit.'],
        ['Dutse Alhaji Junction', 'kubwa_cbd', 9.1200, 7.3800, 'Dutse', 1500, false, 'FCT', 22, 'Dutse Alhaji market.'],
        ['Dutse Baupma Junction', 'kubwa_cbd', 9.1100, 7.3900, 'Dutse', 800, false, 'FCT', 15, 'Baupma settlement.'],
        ['Dei-Dei Junction', 'kubwa_cbd', 9.1100, 7.2800, 'Dei-Dei', 1800, true, 'FCT', 30, 'Dei-Dei market + housing.'],
        ['Dakwa Junction', 'kubwa_cbd', 9.1200, 7.2500, 'Dakwa', 800, false, 'FCT', 18, 'Dei-Dei to Zuba road.'],
        ['Zuba Junction', 'kubwa_cbd', 9.1000, 7.2100, 'Zuba', 1500, true, 'FCT', 28, '1000+ daily from Niger State. Major hub.'],
        ['Madalla Junction', 'kubwa_cbd', 9.1300, 7.2000, 'Madalla', 1200, false, 'Niger', 22, 'Madalla settlement on Zuba road.'],
        ['Suleja Junction', 'kubwa_cbd', 9.1800, 7.1700, 'Suleja', 2000, true, 'Niger', 30, 'Major origin for Niger State commuters.'],
        ['Tafa Junction', 'kubwa_cbd', 9.2500, 7.2500, 'Tafa', 600, false, 'Niger', 20, 'Tafa on Kaduna road.'],
        ['Bwari Junction', 'kubwa_cbd', 9.2833, 7.3800, 'Bwari', 700, false, 'FCT', 20, 'Bwari from Kubwa road.'],
        ['Karmo Junction', 'kubwa_cbd', 9.0400, 7.3800, 'Karmo', 800, false, 'FCT', 15, 'Karmo settlement.'],
        ['Mpape Junction', 'kubwa_cbd', 9.0900, 7.5000, 'Mpape', 1000, false, 'FCT', 18, 'Quarry workers.'],
        ['Life Camp Junction', 'kubwa_cbd', 9.0800, 7.4000, 'Life Camp', 600, false, 'FCT', 12, 'Life Camp estate.'],
        ['Karsana Junction', 'kubwa_cbd', 9.1300, 7.3500, 'Karsana', 500, false, 'FCT', 10, 'Kubwa Express Road.'],
        ['Gwarimpa Gate', 'kubwa_cbd', 9.1000, 7.4100, 'Gwarimpa', 2200, true, 'FCT', 20, '2000+ residents. 3rd Gate Gwarimpa.'],
        ['Kado Junction', 'kubwa_cbd', 9.0900, 7.4200, 'Kado', 600, false, 'FCT', 10, 'Kado estate.'],
        ['Utako Junction', 'kubwa_cbd', 9.0800, 7.4350, 'Utako', 1500, false, 'FCT', 15, 'Utako market.'],
        ['Mabushi Junction', 'kubwa_cbd', 9.0700, 7.4300, 'Mabushi', 1000, false, 'FCT', 12, 'Mabushi district.'],
        ['Jabi Lake Junction', 'kubwa_cbd', 9.0650, 7.4200, 'Jabi', 1200, false, 'FCT', 15, 'Jabi Motor Park.'],
        ['Berger Junction', 'kubwa_cbd', 9.0820, 7.4450, 'Wuse', 3500, true, 'FCT', 20, 'All corridors converge. Major bus stop.'],

        // ---- B. Nyanya-Mararaba Axis (Nyanya → Idu corridor — Nasarawa commuters) ----
        ['Nyanya Under-Bridge', 'nyanya_idu', 8.9800, 7.5800, 'Nyanya', 5000, true, 'FCT', 35, 'Main terminal. 5000+ daily.'],
        ['Mararaba Junction', 'nyanya_idu', 8.9700, 7.5900, 'Mararaba', 4000, true, 'Nasarawa', 30, 'Mararaba, Nasarawa.'],
        ['Masaka Junction', 'nyanya_idu', 8.9500, 7.6500, 'Masaka', 2000, false, 'Nasarawa', 25, 'Masaka, Nasarawa.'],
        ['One Man Village', 'nyanya_idu', 8.9000, 7.7000, 'Keffi', 1200, false, 'Nasarawa', 20, 'Keffi road.'],
        ['Karshi Junction', 'nyanya_idu', 8.8500, 7.5500, 'Karshi', 600, false, 'FCT', 18, 'Karshi, Nasarawa.'],
        ['Karu Junction', 'nyanya_idu', 8.9900, 7.5700, 'Karu', 1500, false, 'FCT', 22, 'Karu settlement.'],
        ['Jikwoyi Junction', 'nyanya_idu', 8.9700, 7.5600, 'Jikwoyi', 1800, false, 'FCT', 24, 'Jikwoyi estate.'],
        ['Kurudu Junction', 'nyanya_idu', 8.9600, 7.5400, 'Kurudu', 1000, false, 'FCT', 18, 'Kurudu estate.'],
        ['Orozo Junction', 'nyanya_idu', 8.9300, 7.5200, 'Orozo', 800, false, 'FCT', 15, 'Orozo settlement.'],
        ['Asokoro Junction', 'nyanya_idu', 9.0500, 7.5200, 'Asokoro', 2500, true, 'FCT', 22, 'AYA Junction — major interchange.'],
        ['Idu Junction', 'nyanya_idu', 9.0522, 7.3245, 'Idu', 1200, true, 'FCT', 20, 'Idu train station — rail commuters.'],

        // ---- C. Lugbe-Airport Road Axis (Lugbe → CBD corridor — airport road) ----
        ['Lugbe Junction', 'lugbe_cbd', 8.9600, 7.3800, 'Lugbe', 2000, true, 'FCT', 25, '1500+ daily. Lugbe Across.'],
        ['Lugbe FHA Junction', 'lugbe_cbd', 8.9500, 7.3700, 'Lugbe', 1200, false, 'FCT', 20, 'Federal Housing Lugbe.'],
        ['Lugbe Shoprite', 'lugbe_cbd', 8.9550, 7.3750, 'Lugbe', 900, false, 'FCT', 15, 'Total filling station.'],
        ['Aco Estate Junction', 'lugbe_cbd', 8.9450, 7.3600, 'Lugbe', 700, false, 'FCT', 15, 'Aco estate.'],
        ['Pyakasa Junction', 'lugbe_cbd', 8.9350, 7.3500, 'Lugbe', 600, false, 'FCT', 12, 'Pyakasa settlement.'],
        ['Airport Toll Gate', 'lugbe_cbd', 9.0060, 7.2700, 'Lugbe', 800, false, 'FCT', 18, 'Bill Clinton Drive.'],
        ['Kuje Junction', 'lugbe_cbd', 8.8800, 7.2300, 'Kuje', 1000, false, 'FCT', 20, 'Kuje on airport road.'],
        ['Gwagwalada Junction', 'lugbe_cbd', 8.9400, 7.0800, 'Gwagwalada', 1500, true, 'FCT', 25, 'University of Abuja students.'],
        ['Giri Junction', 'lugbe_cbd', 8.9200, 7.1500, 'Giri', 600, false, 'FCT', 15, 'Gwagwalada road.'],
        ['Galadimawa Junction', 'lugbe_cbd', 8.9700, 7.4200, 'Galadimawa', 700, false, 'FCT', 12, 'Galadimawa estate.'],
        ['Lokogoma Junction', 'lugbe_cbd', 8.9600, 7.4500, 'Lokogoma', 900, false, 'FCT', 15, 'Lokogoma district.'],
        ['Apo Junction', 'lugbe_cbd', 8.9900, 7.5000, 'Apo', 1200, false, 'FCT', 15, 'Apo Mechanic Village.'],

        // ---- D. CBD & City Centre destinations (garki_wuse in the v6 seed;
        // carried on kubwa_cbd — the Corridor enum has no garki_wuse case) ----
        ['Banex Junction', 'kubwa_cbd', 9.0800, 7.4300, 'Wuse', 1200, false, 'FCT', 15, 'Banex Plaza, Aminu Kano Crescent.'],
        ['Wuse Market Junction', 'kubwa_cbd', 9.0630, 7.4530, 'Wuse', 1800, true, 'FCT', 18, 'Wuse Market — busy all day.'],
        ['Area 1 Junction', 'kubwa_cbd', 9.0430, 7.4850, 'Garki', 1500, false, 'FCT', 15, 'Area 1, Ahmadu Bello Way.'],
        ['Area 3 Junction', 'kubwa_cbd', 9.0350, 7.4900, 'Garki', 800, false, 'FCT', 12, 'Area 3, Constitution Ave.'],
        ['Gudu Junction', 'kubwa_cbd', 9.0200, 7.4900, 'Gudu', 500, false, 'FCT', 10, 'Gudu district.'],
        ['Durumi Junction', 'kubwa_cbd', 9.0300, 7.4600, 'Durumi', 600, false, 'FCT', 12, 'Durumi district.'],
    ];

    public function run(): void
    {
        foreach (self::JUNCTIONS as [$name, $corridor, $lat, $lng, $zone, $volume, $isMajorHub, $state, $waitMins, $notes]) {
            Junction::updateOrCreate(
                ['name' => $name],
                [
                    'corridor' => $corridor,
                    'lat' => $lat,
                    'lng' => $lng,
                    'zone' => $zone,
                    'is_active' => true,
                    'notes' => $notes,
                    'passenger_volume_daily' => $volume,
                    'is_major_hub' => $isMajorHub,
                    'state' => $state,
                    'avg_wait_time_mins' => $waitMins,
                ]
            );
        }

        $this->command?->info('Seeded '.count(self::JUNCTIONS).' Abuja junctions.');
    }
}
