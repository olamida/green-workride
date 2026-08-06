<?php

namespace App\Notifications;

use App\Models\Trip;
use App\Models\TripWaypoint;
use Illuminate\Notifications\Notification;

/**
 * Tells passengers (and the driver) that the bus just crossed a named
 * junction. Delivery follows the codebase pattern: database + application
 * log. FCM push is the intended production channel (guide §6 Workflow 1) —
 * swap in a provider channel when one is configured.
 */
class WaypointReachedNotification extends Notification
{
    public function __construct(
        public Trip $trip,
        public TripWaypoint $waypoint,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'log'];
    }

    /**
     * @return array{title: string, body: string, trip_id: int, waypoint_id: int}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Bus now at '.$this->waypoint->label,
            'body' => 'Your '.$this->trip->route_name.' ride is at '.$this->waypoint->label.', heading to '.$this->trip->destination_text.'.',
            'trip_id' => $this->trip->id,
            'waypoint_id' => $this->waypoint->id,
        ];
    }

    /**
     * @return array{title: string, body: string, trip_id: int, waypoint_id: int}
     */
    public function toLog(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
