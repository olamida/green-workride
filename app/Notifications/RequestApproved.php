<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * Sent to a rider whose share-request was approved. The seat is now held and
 * boarding is allowed — link straight into the ride from the notification.
 */
class RequestApproved extends Notification
{
    public function __construct(public Booking $booking) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'log'];
    }

    /**
     * @return array{title: string, body: string, trip_id: int, booking_id: int}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'You are on the ride',
            'body' => 'Your seat request on '.($this->booking->trip?->route_name ?? 'your shared ride').' was approved by the driver.',
            'trip_id' => $this->booking->trip_id,
            'booking_id' => $this->booking->id,
        ];
    }

    /**
     * @return array{title: string, body: string, trip_id: int, booking_id: int}
     */
    public function toLog(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * @return array{title: string, body: string, trip_id: int, booking_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
