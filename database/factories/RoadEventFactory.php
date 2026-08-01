<?php

namespace Database\Factories;

use App\Enums\RoadEventType;
use App\Models\RoadEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoadEvent>
 */
class RoadEventFactory extends Factory
{
    protected $model = RoadEvent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lat' => fake()->randomFloat(7, 8.90, 9.20),
            'lng' => fake()->randomFloat(7, 7.20, 7.60),
            'type' => RoadEventType::Pothole,
            'severity' => fake()->numberBetween(1, 5),
            'speed' => fake()->randomFloat(2, 5, 80),
            'accelerometer_z' => fake()->randomFloat(2, 0, 30),
            'is_confirmed' => false,
            'road_name' => fake()->randomElement(['Kubwa Expressway', 'Nyanya–Keffi', 'Airport Road']),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['is_confirmed' => true]);
    }

    public function pothole(): static
    {
        return $this->state(fn () => ['type' => RoadEventType::Pothole]);
    }
}
