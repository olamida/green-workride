<?php

namespace Database\Factories;

use App\Models\Workplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workplace>
 */
class WorkplaceFactory extends Factory
{
    protected $model = Workplace::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' (MDA)',
            'acronym' => strtoupper(fake()->lexify('????')),
            'zone' => fake()->randomElement(['Central Business District', 'Garki', 'Wuse', 'Idu']),
            'lat' => fake()->latitude(8.95, 9.10),
            'lng' => fake()->longitude(7.40, 7.55),
            'geofence_radius_m' => 500,
            'is_government' => true,
        ];
    }
}
