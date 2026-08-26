<?php

namespace Database\Factories;

use App\Enums\Corridor;
use App\Models\City;
use App\Models\GtfsRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GtfsRoute>
 */
class GtfsRouteFactory extends Factory
{
    protected $model = GtfsRoute::class;

    public function definition(): array
    {
        $corridor = fake()->randomElement(Corridor::cases());
        $city = City::inRandomOrder()->first();

        return [
            'route_id' => strtoupper(fake()->unique()->lexify('???').'-'.fake()->numerify('###')),
            'agency_id' => 'WR',
            'route_short_name' => str_replace('_', '-', $corridor->value),
            'route_long_name' => $corridor->label(),
            'route_type' => 3,
            'corridor' => $corridor,
            'city_id' => $city?->id,
        ];
    }

    public function forCorridor(Corridor $corridor, ?int $cityId = null): static
    {
        return $this->state(fn () => [
            'route_id' => strtoupper(str_replace('_', '-', $corridor->value)),
            'route_short_name' => str_replace('_', '-', $corridor->value),
            'route_long_name' => $corridor->label(),
            'corridor' => $corridor,
            'city_id' => $cityId,
        ]);
    }
}
