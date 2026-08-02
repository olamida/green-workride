<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Phone verification OTP. Delivery is pluggable: today the code goes to the
 * database + application log (there is no SMS gateway configured yet). To go
 * live, add a provider channel (e.g. Twilio/Termii) behind a config flag.
 */
class SendPhoneOtp extends Notification
{
    public function __construct(public string $code) {}

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
            'title' => 'WorkRide phone verification',
            'body' => "Your WorkRide verification code is {$this->code}. It expires in ".config('workride.phone_verification.otp_ttl_minutes', 10).' minutes.',
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
