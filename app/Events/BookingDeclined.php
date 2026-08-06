<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a driver turns down a pending share request. The rider's "My
 * Rides" list and the driver's live request panel update immediately, and the
 * freed attention returns to the pending queue (no seat was ever held).
 */
class BookingDeclined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("trip.{$this->booking->trip_id}")];
    }

    public function broadcastAs(): string
    {
        return 'BookingDeclined';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'trip_id' => $this->booking->trip_id,
            'passenger_name' => $this->booking->passenger?->name,
        ];
    }
}
