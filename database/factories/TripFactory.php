<?php

namespace Database\Factories;

use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $corridor = fake()->randomElement(Corridor::cases());

        return [
            'driver_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'route_name' => $corridor->label(),
            'corridor' => $corridor,
            'origin_text' => 'Kubwa Junction',
            'destination_text' => 'Federal Secretariat',
            'current_lat' => 9.05,
            'current_lng' => 7.45,
            'total_seats' => 4,
            'available_seats' => 4,
            'fare_per_seat' => 600,
            'is_free_volunteer' => false,
            'status' => TripStatus::Scheduled,
            'departure_time' => now()->addHours(fake()->numberBetween(1, 12))->floorMinute(),
            'waypoints' => null,
        ];
    }

    public function volunteer(): static
    {
        return $this->state(fn () => [
            'is_free_volunteer' => true,
            'fare_per_seat' => 0,
        ]);
    }

    public function forDriver(User $driver): static
    {
        $vehicle = $driver->vehicles()->first() ?? Vehicle::factory()->create(['user_id' => $driver->id]);

        return $this->state(fn () => [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function status(TripStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
