<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Public board live seat counter (section 17 Live Trip Card). Broadcast on the
 * public `trips` channel so any rider watching the board sees the seat count
 * move the moment a seat is taken or freed — without needing to be a trip
 * participant (the private `trip.{id}` channel stays for participants).
 */
class TripSeatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $tripId,
        public int $availableSeats,
        public int $totalSeats,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('trips')];
    }

    public function broadcastAs(): string
    {
        return 'TripSeatsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->tripId,
            'available_seats' => $this->availableSeats,
            'total_seats' => $this->totalSeats,
        ];
    }

    public static function forTrip(Trip $trip): self
    {
        return new self($trip->id, $trip->available_seats, $trip->total_seats);
    }
}
