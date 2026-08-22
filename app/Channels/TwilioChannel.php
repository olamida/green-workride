<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Twilio SMS channel. Sends the OTP via Twilio's API.
 * Docs: https://www.twilio.com/docs/sms/api
 */
class TwilioChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $code = $notification->code;

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (! $sid || ! $token || ! $from || ! $notifiable->phone) {
            return;
        }

        $message = "Your WorkRide verification code is {$code}. It expires in ".config('workride.phone_verification.otp_ttl_minutes', 10).' minutes.';

        Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $notifiable->phone,
                'From' => $from,
                'Body' => $message,
            ])->throw();
    }
}
