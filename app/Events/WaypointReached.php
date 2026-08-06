<?php

namespace App\Events;

use App\Models\Trip;
use App\Models\TripWaypoint;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when the vehicle crosses a waypoint's arrival geofence while a trip
 * is active. Clients on the private `trip.{id}` channel re-render their
 * progress tracker without a page refresh.
 */
class WaypointReached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Trip $trip,
        public TripWaypoint $waypoint,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("trip.{$this->trip->id}")];
    }

    public function broadcastAs(): string
    {
        return 'WaypointReached';
    }

    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->trip->id,
            'waypoint_id' => $this->waypoint->id,
            'label' => $this->waypoint->label,
            'sequence' => $this->waypoint->sequence,
            'reached_at' => $this->waypoint->reached_at?->toIso8601String(),
        ];
    }
}
