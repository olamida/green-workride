<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a driver's live location enters the arrived radius of a
 * passenger's pickup point (guide §6 Workflow 1: "500m away"). Notifies the
 * passenger (FCM push via NotificationService) and broadcasts to the private
 * trip channel so open clients can surface the nudge immediately.
 */
class UserArrivedAtPickup implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Trip $trip,
        public Booking $booking,
        public float $distanceM,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('trip.'.$this->trip->id)];
    }

    public function broadcastAs(): string
    {
        return 'UserArrivedAtPickup';
    }

    /**
     * @return array<string, int|float>
     */
    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->trip->id,
            'booking_id' => $this->booking->id,
            'passenger_id' => $this->booking->passenger_id,
            'distance_m' => round($this->distanceM, 1),
        ];
    }
}
