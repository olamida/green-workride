<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Nigeria',
                'iso_code' => 'NG',
                'currency_code' => 'NGN',
                'currency_symbol' => '₦',
                'phone_prefix' => '+234',
                'timezone' => 'Africa/Lagos',
                'is_active' => true,
            ],
            [
                'name' => 'Kenya',
                'iso_code' => 'KE',
                'currency_code' => 'KES',
                'currency_symbol' => 'KSh',
                'phone_prefix' => '+254',
                'timezone' => 'Africa/Nairobi',
                'is_active' => true,
            ],
            [
                'name' => 'Ghana',
                'iso_code' => 'GH',
                'currency_code' => 'GHS',
                'currency_symbol' => 'GH₵',
                'phone_prefix' => '+233',
                'timezone' => 'Africa/Accra',
                'is_active' => true,
            ],
            [
                'name' => 'Uganda',
                'iso_code' => 'UG',
                'currency_code' => 'UGX',
                'currency_symbol' => 'USh',
                'phone_prefix' => '+256',
                'timezone' => 'Africa/Kampala',
                'is_active' => true,
            ],
            [
                'name' => 'Tanzania',
                'iso_code' => 'TZ',
                'currency_code' => 'TZS',
                'currency_symbol' => 'TSh',
                'phone_prefix' => '+255',
                'timezone' => 'Africa/Dar_es_Salaam',
                'is_active' => true,
            ],
            [
                'name' => 'South Africa',
                'iso_code' => 'ZA',
                'currency_code' => 'ZAR',
                'currency_symbol' => 'R',
                'phone_prefix' => '+27',
                'timezone' => 'Africa/Johannesburg',
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['iso_code' => $country['iso_code']],
                $country
            );
        }
    }
}
