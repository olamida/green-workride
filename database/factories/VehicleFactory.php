<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plate_number' => strtoupper(fake()->unique()->regexify('[A-Z]{3}-\d{3}[A-Z]{2}')),
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Hyundai', 'Kia', 'Mitsubishi']),
            'model' => fake()->randomElement(['Camry', 'Corolla', 'Civic', 'Accent', 'Lancer']),
            'color' => fake()->safeColorName(),
            'seats' => fake()->randomElement([3, 4, 7, 14]),
            'type' => VehicleType::Sedan,
            'papers_verified' => true,
        ];
    }
}
