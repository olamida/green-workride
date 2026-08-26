<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $nigeria = Country::where('iso_code', 'NG')->first();
        $kenya = Country::where('iso_code', 'KE')->first();
        $ghana = Country::where('iso_code', 'GH')->first();
        $uganda = Country::where('iso_code', 'UG')->first();
        $tanzania = Country::where('iso_code', 'TZ')->first();
        $southAfrica = Country::where('iso_code', 'ZA')->first();

        $cities = [
            // Nigeria
            [
                'country_id' => $nigeria->id,
                'name' => 'Abuja',
                'slug' => 'abuja',
                'center_lat' => 9.0765,
                'center_lng' => 7.3986,
                'bounds_min_lat' => 8.60,
                'bounds_max_lat' => 9.40,
                'bounds_min_lng' => 6.90,
                'bounds_max_lng' => 7.70,
                'is_active' => true,
            ],
            [
                'country_id' => $nigeria->id,
                'name' => 'Lagos',
                'slug' => 'lagos',
                'center_lat' => 6.5244,
                'center_lng' => 3.3792,
                'bounds_min_lat' => 6.20,
                'bounds_max_lat' => 6.80,
                'bounds_min_lng' => 3.00,
                'bounds_max_lng' => 3.70,
                'is_active' => true,
            ],
            [
                'country_id' => $nigeria->id,
                'name' => 'Port Harcourt',
                'slug' => 'port-harcourt',
                'center_lat' => 4.8156,
                'center_lng' => 7.0498,
                'is_active' => true,
            ],
            // Kenya
            [
                'country_id' => $kenya->id,
                'name' => 'Nairobi',
                'slug' => 'nairobi',
                'center_lat' => -1.2921,
                'center_lng' => 36.8219,
                'bounds_min_lat' => -1.50,
                'bounds_max_lat' => -1.10,
                'bounds_min_lng' => 36.60,
                'bounds_max_lng' => 37.10,
                'is_active' => true,
            ],
            [
                'country_id' => $kenya->id,
                'name' => 'Mombasa',
                'slug' => 'mombasa',
                'center_lat' => -4.0435,
                'center_lng' => 39.6682,
                'is_active' => true,
            ],
            // Ghana
            [
                'country_id' => $ghana->id,
                'name' => 'Accra',
                'slug' => 'accra',
                'center_lat' => 5.6037,
                'center_lng' => -0.1870,
                'bounds_min_lat' => 5.40,
                'bounds_max_lat' => 5.80,
                'bounds_min_lng' => -0.40,
                'bounds_max_lng' => 0.10,
                'is_active' => true,
            ],
            [
                'country_id' => $ghana->id,
                'name' => 'Kumasi',
                'slug' => 'kumasi',
                'center_lat' => 6.6885,
                'center_lng' => -1.6244,
                'is_active' => true,
            ],
            // Uganda
            [
                'country_id' => $uganda->id,
                'name' => 'Kampala',
                'slug' => 'kampala',
                'center_lat' => 0.3476,
                'center_lng' => 32.5825,
                'bounds_min_lat' => 0.10,
                'bounds_max_lat' => 0.50,
                'bounds_min_lng' => 32.30,
                'bounds_max_lng' => 32.80,
                'is_active' => true,
            ],
            // Tanzania
            [
                'country_id' => $tanzania->id,
                'name' => 'Dar es Salaam',
                'slug' => 'dar-es-salaam',
                'center_lat' => -6.7924,
                'center_lng' => 39.2083,
                'is_active' => true,
            ],
            // South Africa
            [
                'country_id' => $southAfrica->id,
                'name' => 'Johannesburg',
                'slug' => 'johannesburg',
                'center_lat' => -26.2041,
                'center_lng' => 28.0473,
                'is_active' => true,
            ],
            [
                'country_id' => $southAfrica->id,
                'name' => 'Cape Town',
                'slug' => 'cape-town',
                'center_lat' => -33.9249,
                'center_lng' => 18.4241,
                'is_active' => true,
            ],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(
                ['slug' => $city['slug']],
                $city
            );
        }
    }
}
