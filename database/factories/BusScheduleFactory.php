<?php

namespace Database\Factories;

use App\Enums\BusScheduleStatus;
use App\Models\BusSchedule;
use App\Models\GtfsRoute;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusSchedule>
 */
class BusScheduleFactory extends Factory
{
    protected $model = BusSchedule::class;

    public function definition(): array
    {
        return [
            'route_id' => GtfsRoute::factory(),
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => User::factory(),
            'departure_time' => '06:30',
            'end_time' => '09:00',
            'frequency_minutes' => 15,
            'days_of_week' => ['mon', 'tue', 'wed', 'thu', 'fri'],
            'status' => BusScheduleStatus::Active,
        ];
    }
}
