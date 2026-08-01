<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TripCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Trip $trip, public ?string $reason = null) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("trip.{$this->trip->id}")];
    }

    public function broadcastAs(): string
    {
        return 'TripCancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->trip->id,
            'status' => $this->trip->status->value,
            'reason' => $this->reason,
        ];
    }
}
