<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'iso_code' => strtoupper(fake()->unique()->lexify('??')),
            'currency_code' => strtoupper(fake()->lexify('???')),
            'currency_symbol' => fake()->currencySymbol(),
            'phone_prefix' => '+'.fake()->numerify('###'),
            'timezone' => fake()->timezone(),
            'is_active' => true,
        ];
    }
}
