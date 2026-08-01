<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class BookingConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("trip.{$this->booking->trip_id}")];
    }

    public function broadcastAs(): string
    {
        return 'BookingConfirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'trip_id' => $this->booking->trip_id,
            'passenger_name' => $this->booking->passenger?->name,
            'available_seats' => $this->booking->trip->available_seats,
        ];
    }
}
