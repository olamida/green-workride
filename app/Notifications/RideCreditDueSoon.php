<?php

namespace App\Notifications;

use App\Models\RideCredit;
use Illuminate\Notifications\Notification;

/**
 * Pre-due ride-credit reminder (WORKRIDE-DESIGN-REVIEWS §3 backlog + roadmap
 * 3.4): "your owed seat(s) come due soon — repay by driving or they go
 * overdue". Delivery is database + log (SMS slots in when a gateway lands),
 * exactly like SendPhoneOtp. The job marks reminder_sent_at so a rider is
 * never re-reminded on every nightly run.
 */
class RideCreditDueSoon extends Notification
{
    public function __construct(public RideCredit $credit) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'log'];
    }

    /**
     * @return array{title: string, body: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Your WorkRide seat credit is due soon',
            'body' => "You owe {$this->credit->outstandingSeats()} seat(s) from a ride on ".$this->credit->due_date?->format('d M Y').'. Repay by driving and carrying passengers, or the credit goes overdue and blocks new ride-credit bookings.',
        ];
    }

    /**
     * @return array{title: string, body: string}
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
