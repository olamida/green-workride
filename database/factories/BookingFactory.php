<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'passenger_id' => User::factory(),
            'pickup_lat' => 9.05,
            'pickup_lng' => 7.45,
            'status' => BookingStatus::Confirmed,
            'fare_paid' => 600,
            'payment_method' => PaymentMethod::Wallet,
        ];
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
