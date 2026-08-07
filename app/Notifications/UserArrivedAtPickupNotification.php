<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * The "500m away" passenger nudge (guide §6 Workflow 1, roadmap P3.2).
 *
 * Channels: database + log (change-control trail) like every WorkRide notice.
 * When push is enabled, NotificationService additionally delivers the toFcm()
 * payload via FCM so a closed browser still sees the alert.
 */
class UserArrivedAtPickupNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Trip $trip,
        public Booking $booking,
        public float $distanceM,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'log'];
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Your ride is almost here',
            'body' => 'Your '.$this->trip->route_name.' driver is '.round($this->distanceM).' m away — please wait at your pickup point.',
            'trip_id' => $this->trip->id,
            'booking_id' => $this->booking->id,
            'distance_m' => (int) round($this->distanceM),
            'url' => route('trips.show', $this->trip),
        ];
    }

    /**
     * FCM push payload consumed by NotificationService -> FcmService.
     *
     * @return array<string, string|array<string, string>>
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Your ride is almost here',
            'body' => 'Your '.$this->trip->route_name.' driver is '.round($this->distanceM).' m away — please wait at your pickup point.',
            'data' => [
                'trip_id' => (string) $this->trip->id,
                'booking_id' => (string) $this->booking->id,
                'url' => route('trips.show', $this->trip),
            ],
        ];
    }
}
