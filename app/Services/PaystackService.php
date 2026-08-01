<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Paystack REST API and webhook signature verification.
 *
 * Charges are denominated in kobo on Paystack; this service converts to/from
 * naira at the edges so the rest of the app only ever deals in naira.
 */
class PaystackService
{
    public function isConfigured(): bool
    {
        return filled($this->secretKey()) && filled($this->webhookSecret());
    }

    /**
     * Initialise a one-off charge and return the Paystack redirect URL.
     *
     * @return array{authorization_url: string, reference: string, access_code: string}|null
     */
    public function initialize(string $email, float $amount, string $reference): ?array
    {
        $response = $this->request('POST', '/transaction/initialize', [
            'email' => $email,
            'amount' => round($amount * 100),
            'reference' => $reference,
            'currency' => 'NGN',
            'metadata' => ['workride_ref' => $reference],
        ]);

        return ($response->successful() && $response->json('status'))
            ? $response->json('data')
            : null;
    }

    /**
     * Verify a transaction by reference (source of truth for webhook crediting).
     */
    public function verify(string $reference): ?array
    {
        $response = $this->request('GET', "/transaction/verify/{$reference}");

        return ($response->successful() && $response->json('status'))
            ? $response->json('data')
            : null;
    }

    /**
     * Verify the HMAC-SHA512 signature Paystack attaches to every webhook.
     */
    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (! $signature || ! $this->webhookSecret()) {
            return false;
        }

        $computed = hash_hmac('sha512', $payload, $this->webhookSecret());

        return hash_equals($computed, $signature);
    }

    private function request(string $method, string $path, array $body = []): Response
    {
        try {
            return Http::withToken($this->secretKey())
                ->acceptJson()
                ->baseUrl(config('services.paystack.base_url'))
                ->send($method, $path, $method === 'GET' ? ['query' => $body] : ['json' => $body]);
        } catch (ConnectionException) {
            return new Response(
                new \GuzzleHttp\Psr7\Response(
                    503,
                    ['Content-Type' => 'application/json'],
                    json_encode(['status' => false, 'message' => 'Paystack unreachable']),
                ),
            );
        }
    }

    private function secretKey(): ?string
    {
        return config('services.paystack.secret_key');
    }

    private function webhookSecret(): ?string
    {
        return config('services.paystack.webhook_secret');
    }
}
