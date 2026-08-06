<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a rider asks to join a shared trip via a share-code link before
 * any seat is reserved. The driver's live view (and the Control Tower) see the
 * request appear in the pending queue without a page refresh.
 */
class BookingRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("trip.{$this->booking->trip_id}")];
    }

    public function broadcastAs(): string
    {
        return 'BookingRequested';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'trip_id' => $this->booking->trip_id,
            'passenger_name' => $this->booking->passenger?->name,
            'share_code' => $this->booking->share_code,
        ];
    }
}
