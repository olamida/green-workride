<?php

namespace Database\Factories;

use App\Enums\Corridor;
use App\Models\TripTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripTemplate>
 */
class TripTemplateFactory extends Factory
{
    protected $model = TripTemplate::class;

    public function definition(): array
    {
        return [
            'driver_id' => User::factory(),
            'name' => fake()->randomElement(['Morning commute', 'Afternoon run', 'Evening home']),
            'corridor' => fake()->randomElement(Corridor::cases()),
            'origin_text' => fake()->randomElement(['Kubwa Junction', 'Nyanya Under-Bridge', 'Lugbe Roundabout']),
            'destination_text' => fake()->randomElement(['Federal Secretariat', 'Idu Industrial', 'CBD']),
            'departure_time' => fake()->randomElement(['06:30', '06:45', '07:00', '17:30']),
            'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
            'total_seats' => 4,
            'fare_per_seat' => 600,
            'is_free_volunteer' => false,
            'women_only' => false,
            'is_active' => true,
            'times_used' => 0,
        ];
    }

    public function volunteer(): static
    {
        return $this->state(fn () => [
            'is_free_volunteer' => true,
            'fare_per_seat' => 0,
        ]);
    }
}
