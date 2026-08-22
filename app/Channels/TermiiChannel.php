<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Termii SMS channel for Nigeria. Sends the OTP via Termii's API.
 * Docs: https://docs.termii.com/
 */
class TermiiChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $code = $notification->code;

        $apiKey = config('services.termii.key');
        $senderId = config('services.termii.sender_id', 'WorkRide');
        $baseUrl = config('services.termii.base_url', 'https://api.ng.termii.com');

        if (! $apiKey || ! $notifiable->phone) {
            return;
        }

        $message = "Your WorkRide verification code is {$code}. It expires in ".config('workride.phone_verification.otp_ttl_minutes', 10).' minutes.';

        Http::asForm()
            ->post("{$baseUrl}/api/sms/send", [
                'api_key' => $apiKey,
                'to' => $notifiable->phone,
                'from' => $senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
            ])->throw();
    }
}
