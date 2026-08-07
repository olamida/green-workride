<?php

namespace App\Services;

use App\Enums\PushPlatform;
use App\Models\DeviceToken;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Firebase Cloud Messaging legacy HTTP send API.
 *
 * Feature-gated: when workride.push.enabled is false (default) or no
 * services.fcm.server_key is set, isConfigured() is false and every call is a
 * silent no-op — the same defensive pattern as PaystackService. Tokens are
 * registered per-device via PushTokenController; a user may own several.
 */
class FcmService
{
    public function isConfigured(): bool
    {
        return (bool) config('workride.push.enabled')
            && filled($this->serverKey());
    }

    /**
     * Send a push to every device token a user owns. Returns how many tokens
     * accepted the message.
     *
     * @param  array<string, string|int>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $sent = 0;
        foreach ($user->deviceTokens as $deviceToken) {
            if ($this->sendToToken($deviceToken->token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param  array<string, string|int>  $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $response = $this->request($token, $title, $body, $data);

        return $response->successful() && ($response->json('success') ?? 0) > 0;
    }

    /**
     * Upsert a registration token for a user (idempotent per user+token).
     */
    public function register(User $user, string $token, PushPlatform $platform = PushPlatform::Web): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['user_id' => $user->id, 'token' => $token],
            ['platform' => $platform, 'last_used_at' => now()],
        );
    }

    /**
     * Forget a registration token. Returns the number of rows deleted.
     */
    public function unregister(User $user, string $token): int
    {
        return DeviceToken::where('user_id', $user->id)
            ->where('token', $token)
            ->delete();
    }

    /**
     * @param  array<string, string|int>  $data
     */
    private function request(string $token, string $title, string $body, array $data): \Illuminate\Http\Client\Response
    {
        try {
            return Http::withHeaders(['Authorization' => 'key='.$this->serverKey()])
                ->acceptJson()
                ->post(config('services.fcm.endpoint'), [
                    'to' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'priority' => 'high',
                ]);
        } catch (ConnectionException) {
            return new \Illuminate\Http\Client\Response(new Response(503, ['Content-Type' => 'application/json'], json_encode([
                'success' => 0,
                'failure' => 1,
                'results' => [['error' => 'FCM unreachable']],
            ]) ?: '{}'));
        }
    }

    private function serverKey(): ?string
    {
        return config('services.fcm.server_key');
    }
}
