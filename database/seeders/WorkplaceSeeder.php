<?php

namespace Database\Seeders;

use App\Models\Workplace;
use Illuminate\Database\Seeder;

/**
 * Seeds 45 FCT MDAs with coordinates near the Federal Secretariat / Three Arms Zone
 * and their satellite residential geofences (the districts workers commute to/from).
 *
 * Latitudes/longitudes are representative approximations for the FCT.
 */
class WorkplaceSeeder extends Seeder
{
    public function run(): void
    {
        $cbd = ['lat' => 9.0450, 'lng' => 7.4922]; // Three Arms Zone / Federal Secretariat

        $mdas = [
            ['name' => 'Federal Ministry of Works', 'acronym' => 'FMW', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Finance', 'acronym' => 'FMF', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Education', 'acronym' => 'FME', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Health', 'acronym' => 'FMH', 'zone' => 'Garki'],
            ['name' => 'Federal Ministry of Transportation', 'acronym' => 'FMOT', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Agriculture and Food Security', 'acronym' => 'FMAFS', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Justice', 'acronym' => 'FMOJ', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Foreign Affairs', 'acronym' => 'FMFA', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Power', 'acronym' => 'FMP', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Petroleum Resources', 'acronym' => 'FMPR', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Labour and Employment', 'acronym' => 'FMLE', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Environment', 'acronym' => 'FME', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Communication and Digital Economy', 'acronym' => 'FMCDE', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Information and National Orientation', 'acronym' => 'FMINO', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Water Resources and Sanitation', 'acronym' => 'FMWR', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Women Affairs', 'acronym' => 'FMWA', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Youth Development', 'acronym' => 'FMYD', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Humanitarian Affairs and Poverty Alleviation', 'acronym' => 'FMHAPA', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Aviation and Aerospace Development', 'acronym' => 'FMAAD', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Science and Technology', 'acronym' => 'FMST', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Solid Minerals Development', 'acronym' => 'FMSMD', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Sports Development', 'acronym' => 'FMSD', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Tourism and Culture', 'acronym' => 'FMTC', 'zone' => 'Central Business District'],
            ['name' => 'Federal Ministry of Defence', 'acronym' => 'FMD', 'zone' => 'Central Business District'],
            ['name' => 'Office of the Head of the Civil Service of the Federation', 'acronym' => 'OHCSF', 'zone' => 'Central Business District'],
            ['name' => 'Office of the Secretary to the Government of the Federation', 'acronym' => 'OSGF', 'zone' => 'Central Business District'],
            ['name' => 'FCTA — Transport Secretariat', 'acronym' => 'FCTA', 'zone' => 'Central Business District'],
            ['name' => 'FCTA — Area Councils Service Commission', 'acronym' => 'ACSC', 'zone' => 'Central Business District'],
            ['name' => 'National Assembly — Civil Service', 'acronym' => 'NASS', 'zone' => 'Central Business District'],
            ['name' => 'Federal Civil Service Commission', 'acronym' => 'FCSC', 'zone' => 'Central Business District'],
            ['name' => 'Nigeria Police Force — Force Headquarters', 'acronym' => 'NPF', 'zone' => 'Central Business District'],
            ['name' => 'Nigerian Immigration Service', 'acronym' => 'NIS', 'zone' => 'Central Business District'],
            ['name' => 'Federal Road Safety Corps', 'acronym' => 'FRSC', 'zone' => 'Central Business District'],
            ['name' => 'National Identity Management Commission', 'acronym' => 'NIMC', 'zone' => 'Central Business District'],
            ['name' => 'Nigeria Communication Commission', 'acronym' => 'NCC', 'zone' => 'Central Business District'],
            ['name' => 'National Information Technology Development Agency', 'acronym' => 'NITDA', 'zone' => 'Central Business District'],
            ['name' => 'Central Bank of Nigeria', 'acronym' => 'CBN', 'zone' => 'Central Business District'],
            ['name' => 'Federal Inland Revenue Service', 'acronym' => 'FIRS', 'zone' => 'Central Business District'],
            ['name' => 'Nigerian Ports Authority', 'acronym' => 'NPA', 'zone' => 'Central Business District'],
            ['name' => 'Nigerian National Petroleum Company', 'acronym' => 'NNPC', 'zone' => 'Central Business District'],
            ['name' => 'National Bureau of Statistics', 'acronym' => 'NBS', 'zone' => 'Central Business District'],
            ['name' => 'National Emergency Management Agency', 'acronym' => 'NEMA', 'zone' => 'Garki'],
            ['name' => 'Nigeria Atomic Energy Commission', 'acronym' => 'NAEC', 'zone' => 'Garki'],
            ['name' => 'National Space Research and Development Agency', 'acronym' => 'NASRDA', 'zone' => 'Garki'],
            ['name' => 'National Universities Commission', 'acronym' => 'NUC', 'zone' => 'Wuse'],
        ];

        foreach ($mdas as $i => $mda) {
            Workplace::updateOrCreate(
                ['name' => $mda['name']],
                [
                    'acronym' => $mda['acronym'],
                    'zone' => $mda['zone'],
                    'lat' => round($cbd['lat'] + (mt_rand(-15, 15) / 10000), 7),
                    'lng' => round($cbd['lng'] + (mt_rand(-15, 15) / 10000), 7),
                    'geofence_radius_m' => 500,
                    'is_government' => true,
                ],
            );
        }

        $this->command?->info('Seeded '.count($mdas).' FCT MDA workplaces.');
    }
}
