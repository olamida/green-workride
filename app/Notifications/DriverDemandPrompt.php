<?php

namespace App\Notifications;

use App\Enums\Corridor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Demand → supply nudge (gallery "service planning" Phase 3): "N people want
 * Kubwa → CBD right now — publish and fill the gap." Sent to qualified idle
 * drivers. Channels: database + log (change-control trail) like every WorkRide
 * notice; when push is enabled, NotificationService additionally delivers the
 * toFcm() payload via FCM so a closed browser still sees the nudge.
 */
class DriverDemandPrompt extends Notification
{
    use Queueable;

    public function __construct(
        public Corridor $corridor,
        public int $peopleCount,
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
            'title' => 'Demand wants you — '.$this->corridor->label(),
            'body' => $this->peopleCount.' people want a ride on '.$this->corridor->label().' right now. Publish your trip to fill the gap.',
            'corridor' => $this->corridor->value,
            'people_count' => $this->peopleCount,
            'url' => route('trips.create', ['corridor' => $this->corridor->value]),
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
            'title' => 'Demand wants you — '.$this->corridor->label(),
            'body' => $this->peopleCount.' people want a ride right now. Publish to fill the gap.',
            'data' => [
                'corridor' => $this->corridor->value,
                'people_count' => (string) $this->peopleCount,
                'url' => route('trips.create', ['corridor' => $this->corridor->value]),
            ],
        ];
    }
}
