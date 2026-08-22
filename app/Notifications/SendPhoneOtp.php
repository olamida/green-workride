<?php

namespace App\Notifications;

use App\Channels\TermiiChannel;
use App\Channels\TwilioChannel;
use Illuminate\Notifications\Notification;

/**
 * Phone verification OTP. Delivery is pluggable: codes go to the database +
 * application log by default. When WORKRIDE_SMS_ENABLED=true, the configured
 * provider (Termii or Twilio) sends the SMS via a custom channel.
 */
class SendPhoneOtp extends Notification
{
    public function __construct(public string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'log'];

        if (config('workride.phone_verification.sms_channel') !== 'log') {
            $provider = config('workride.phone_verification.sms_channel');
            $channels[] = $provider === 'twilio' ? TwilioChannel::class : TermiiChannel::class;
        }

        return $channels;
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
