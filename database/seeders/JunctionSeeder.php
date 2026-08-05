<?php

namespace Database\Seeders;

use App\Models\Junction;
use Illuminate\Database\Seeder;

/**
 * Seeds the junction catalog — the real Abuja high-traffic passenger hotspots
 * where thousands wait daily (5:30-9am and 4-8pm) for demand surveys, GTFS
 * stops and trip waypoints. Every coordinate is on the real road network.
 *
 * Columns adapted to the actual `junctions` schema (name, corridor, lat, lng,
 * zone, is_active, notes) — the v4.0 spec's slug/volume/union columns are not
 * part of this migration.
 */
class JunctionSeeder extends Seeder
{
    public function run(): void
    {
        $junctions = [
            // ---- A. Kubwa Axis (Kubwa → CBD corridor — primary, heaviest) ----
            ['Kubwa Junction', 'kubwa_cbd', 9.1500, 7.3333, 'Kubwa', '2000+ passengers daily. Main Kubwa terminal.'],
            ['Kubwa FHA Junction', 'kubwa_cbd', 9.1650, 7.3300, 'Kubwa', 'FHA Kubwa estate exit.'],
            ['Kubwa Second Gate', 'kubwa_cbd', 9.1550, 7.3400, 'Kubwa', '2nd Gate estate exit.'],
            ['Dutse Alhaji Junction', 'kubwa_cbd', 9.1200, 7.3800, 'Dutse', 'Dutse Alhaji market.'],
            ['Dutse Baupma Junction', 'kubwa_cbd', 9.1100, 7.3900, 'Dutse', 'Baupma settlement.'],
            ['Dei-Dei Junction', 'kubwa_cbd', 9.1100, 7.2800, 'Dei-Dei', 'Dei-Dei market + housing.'],
            ['Dakwa Junction', 'kubwa_cbd', 9.1200, 7.2500, 'Dakwa', 'Dei-Dei to Zuba road.'],
            ['Zuba Junction', 'kubwa_cbd', 9.1000, 7.2100, 'Zuba', '1000+ daily from Niger State. Major hub.'],
            ['Madalla Junction', 'kubwa_cbd', 9.1300, 7.2000, 'Madalla', 'Madalla settlement on Zuba road.'],
            ['Suleja Junction', 'kubwa_cbd', 9.1800, 7.1700, 'Suleja', 'Major origin for Niger State commuters.'],
            ['Tafa Junction', 'kubwa_cbd', 9.2500, 7.2500, 'Tafa', 'Tafa on Kaduna road.'],
            ['Bwari Junction', 'kubwa_cbd', 9.2833, 7.3800, 'Bwari', 'Bwari from Kubwa road.'],
            ['Karmo Junction', 'kubwa_cbd', 9.0400, 7.3800, 'Karmo', 'Karmo settlement.'],
            ['Mpape Junction', 'kubwa_cbd', 9.0900, 7.5000, 'Mpape', 'Quarry workers.'],
            ['Life Camp Junction', 'kubwa_cbd', 9.0800, 7.4000, 'Life Camp', 'Life Camp estate.'],
            ['Karsana Junction', 'kubwa_cbd', 9.1300, 7.3500, 'Karsana', 'Kubwa Express Road.'],
            ['Gwarimpa Gate', 'kubwa_cbd', 9.1000, 7.4100, 'Gwarimpa', '2000+ residents. 3rd Gate Gwarimpa.'],
            ['Kado Junction', 'kubwa_cbd', 9.0900, 7.4200, 'Kado', 'Kado estate.'],
            ['Utako Junction', 'kubwa_cbd', 9.0800, 7.4350, 'Utako', 'Utako market.'],
            ['Mabushi Junction', 'kubwa_cbd', 9.0700, 7.4300, 'Mabushi', 'Mabushi district.'],
            ['Jabi Lake Junction', 'kubwa_cbd', 9.0650, 7.4200, 'Jabi', 'Jabi Motor Park.'],
            ['Berger Junction', 'kubwa_cbd', 9.0820, 7.4450, 'Wuse', 'All corridors converge. Major bus stop.'],

            // ---- B. Nyanya-Mararaba Axis (Nyanya → Idu corridor — Nasarawa commuters) ----
            ['Nyanya Under-Bridge', 'nyanya_idu', 8.9800, 7.5800, 'Nyanya', 'Main terminal. 5000+ daily.'],
            ['Mararaba Junction', 'nyanya_idu', 8.9700, 7.5900, 'Mararaba', 'Mararaba, Nasarawa.'],
            ['Masaka Junction', 'nyanya_idu', 8.9500, 7.6500, 'Masaka', 'Masaka, Nasarawa.'],
            ['One Man Village', 'nyanya_idu', 8.9000, 7.7000, 'Keffi', 'Keffi road.'],
            ['Karshi Junction', 'nyanya_idu', 8.8500, 7.5500, 'Karshi', 'Karshi, Nasarawa.'],
            ['Karu Junction', 'nyanya_idu', 8.9900, 7.5700, 'Karu', 'Karu settlement.'],
            ['Jikwoyi Junction', 'nyanya_idu', 8.9700, 7.5600, 'Jikwoyi', 'Jikwoyi estate.'],
            ['Kurudu Junction', 'nyanya_idu', 8.9600, 7.5400, 'Kurudu', 'Kurudu estate.'],
            ['Orozo Junction', 'nyanya_idu', 8.9300, 7.5200, 'Orozo', 'Orozo settlement.'],
            ['Asokoro Junction', 'nyanya_idu', 9.0500, 7.5200, 'Asokoro', 'AYA Junction — major interchange.'],
            ['Idu Junction', 'nyanya_idu', 9.0522, 7.3245, 'Idu', 'Idu train station — rail commuters.'],

            // ---- C. Lugbe-Airport Road Axis (Lugbe → CBD corridor — airport road) ----
            ['Lugbe Junction', 'lugbe_cbd', 8.9600, 7.3800, 'Lugbe', '1500+ daily. Lugbe Across.'],
            ['Lugbe FHA Junction', 'lugbe_cbd', 8.9500, 7.3700, 'Lugbe', 'Federal Housing Lugbe.'],
            ['Lugbe Shoprite', 'lugbe_cbd', 8.9550, 7.3750, 'Lugbe', 'Total filling station.'],
            ['Aco Estate Junction', 'lugbe_cbd', 8.9450, 7.3600, 'Lugbe', 'Aco estate.'],
            ['Pyakasa Junction', 'lugbe_cbd', 8.9350, 7.3500, 'Lugbe', 'Pyakasa settlement.'],
            ['Airport Toll Gate', 'lugbe_cbd', 9.0060, 7.2700, 'Lugbe', 'Bill Clinton Drive.'],
            ['Kuje Junction', 'lugbe_cbd', 8.8800, 7.2300, 'Kuje', 'Kuje on airport road.'],
            ['Gwagwalada Junction', 'lugbe_cbd', 8.9400, 7.0800, 'Gwagwalada', 'University of Abuja students.'],
            ['Giri Junction', 'lugbe_cbd', 8.9200, 7.1500, 'Giri', 'Gwagwalada road.'],
            ['Galadimawa Junction', 'lugbe_cbd', 8.9700, 7.4200, 'Galadimawa', 'Galadimawa estate.'],
            ['Lokogoma Junction', 'lugbe_cbd', 8.9600, 7.4500, 'Lokogoma', 'Lokogoma district.'],
            ['Apo Junction', 'lugbe_cbd', 8.9900, 7.5000, 'Apo', 'Apo Mechanic Village.'],
        ];

        foreach ($junctions as [$name, $corridor, $lat, $lng, $zone, $notes]) {
            Junction::updateOrCreate(
                ['name' => $name],
                ['corridor' => $corridor, 'lat' => $lat, 'lng' => $lng, 'zone' => $zone, 'is_active' => true, 'notes' => $notes]
            );
        }

        $this->command?->info('Seeded '.count($junctions).' Abuja junctions.');
    }
}
