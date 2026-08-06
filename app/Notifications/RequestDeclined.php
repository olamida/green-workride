<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * Sent to a rider whose share-request was declined. No money ever moved — the
 * seat simply was not held — so this is purely informational + a retry path.
 */
class RequestDeclined extends Notification
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
            'title' => 'Ride request declined',
            'body' => 'The driver turned down your request to join '.($this->booking->trip?->route_name ?? 'your shared ride').'. No payment was taken.',
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
