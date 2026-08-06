<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * Sent to a driver when a rider requests a seat through a shared ride link.
 * The seat is NOT reserved yet — the driver approves (seat held + boarding
 * allowed) or declines (rider is told, no money moves either way).
 */
class BookingRequested extends Notification
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
     * @return array{title: string, body: string, booking_id: int}
     */
    public function toDatabase(object $notifiable): array
    {
        $name = $this->booking->passenger?->name ?? 'A rider';

        return [
            'title' => 'New ride request',
            'body' => "{$name} asked to join {$this->booking->trip?->route_name}. Approve them to hold the seat.",
            'booking_id' => $this->booking->id,
        ];
    }

    /**
     * @return array{title: string, body: string, booking_id: int}
     */
    public function toLog(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * @return array{title: string, body: string, booking_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
