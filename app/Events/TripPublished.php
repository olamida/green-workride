<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TripPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Trip $trip) {}

    public function broadcastOn(): array
    {
        return [new Channel('trips')];
    }

    public function broadcastAs(): string
    {
        return 'TripPublished';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->trip->id,
            'corridor' => $this->trip->corridor->value,
            'route_name' => $this->trip->route_name,
            'origin_text' => $this->trip->origin_text,
            'destination_text' => $this->trip->destination_text,
            'departure_time' => $this->trip->departure_time->toIso8601String(),
            'total_seats' => $this->trip->total_seats,
            'available_seats' => $this->trip->available_seats,
            'fare_per_seat' => (float) $this->trip->fare_per_seat,
            'is_free_volunteer' => $this->trip->is_free_volunteer,
        ];
    }
}
