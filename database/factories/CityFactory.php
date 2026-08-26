<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'country_id' => null, // set in tests
            'name' => fake()->city(),
            'slug' => fake()->unique()->slug(),
            'center_lat' => fake()->latitude(),
            'center_lng' => fake()->longitude(),
            'bounds_min_lat' => null,
            'bounds_max_lat' => null,
            'bounds_min_lng' => null,
            'bounds_max_lng' => null,
            'is_active' => true,
        ];
    }
}
