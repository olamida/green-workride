<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Sends a notification the WorkRide way: through the usual channels the
 * notification declares (database + log for the change-control trail), plus an
 * FCM push when the notification exposes a toFcm() payload and push is enabled.
 *
 * This is what makes "500m away" reach a closed browser (roadmap P3.2,
 * guide §6 Workflow 1) without sprinkling FcmService calls across every flow.
 */
class NotificationService
{
    public function __construct(private FcmService $fcm) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        NotificationFacade::send($notifiable, $notification);

        if (! $this->fcm->isConfigured() || ! method_exists($notification, 'toFcm')) {
            return;
        }

        $notifiables = $notifiable instanceof \Traversable ? $notifiable : [$notifiable];

        foreach ($notifiables as $recipient) {
            if (! $recipient instanceof User) {
                continue;
            }

            $payload = $notification->toFcm($recipient);
            if (! is_array($payload)) {
                continue;
            }

            $this->fcm->sendToUser(
                $recipient,
                $payload['title'] ?? 'WorkRide',
                $payload['body'] ?? '',
                $payload['data'] ?? [],
            );
        }
    }
}
