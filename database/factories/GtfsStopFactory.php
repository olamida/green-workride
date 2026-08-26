<?php

namespace Database\Factories;

use App\Models\GtfsStop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GtfsStop>
 */
class GtfsStopFactory extends Factory
{
    protected $model = GtfsStop::class;

    public function definition(): array
    {
        return [
            'stop_id' => fake()->unique()->bothify('???-##'),
            'stop_name' => fake()->streetName(),
            'stop_lat' => fake()->latitude(8.95, 9.10),
            'stop_lon' => fake()->longitude(7.40, 7.55),
            'corridor' => fake()->randomElement(['kubwa_cbd', 'nyanya_idu', 'lugbe_cbd']),
            'zone' => fake()->city(),
            'city_id' => null, // set in tests
        ];
    }
}
