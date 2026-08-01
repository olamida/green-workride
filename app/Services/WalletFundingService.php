<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Orchestrates Paystack-funded wallet top-ups.
 *
 * A top-up flows: user submits amount → Paystack transaction is initialized
 * with a `WR-{userId}-{random}` reference → Paystack charges the user →
 * `charge.success` webhook arrives → the confirmed amount is credited to the
 * user's cash balance, idempotently keyed by the Paystack reference.
 */
class WalletFundingService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly PaystackService $paystack,
    ) {}

    public function referenceFor(User $user): string
    {
        return 'WR-'.$user->id.'-'.strtoupper(Str::random(12));
    }

    /**
     * Credit a verified top-up. Returns false when the reference was already
     * applied (duplicate webhook or replayed request).
     */
    public function creditTopUp(User $user, float $amount, string $txRef, ?string $gatewayRef = null): bool
    {
        if (Transaction::where('tx_ref', $txRef)->exists()) {
            return false;
        }

        $this->wallets->creditCash(
            $user,
            $amount,
            "TOPUP-{$txRef}",
            'Paystack wallet top-up',
            [
                'gateway' => PaymentMethod::Paystack->value,
                'gateway_ref' => $gatewayRef,
                'tx_ref' => $txRef,
            ],
        );

        Transaction::where('reference', "TOPUP-{$txRef}")->update([
            'type' => TransactionType::TopUp->value,
            'tx_ref' => $txRef,
            'gateway_ref' => $gatewayRef,
        ]);

        return true;
    }

    /**
     * Validate and process a Paystack webhook payload.
     *
     * @return array{ack: bool, reason: string} `ack` controls whether the
     *                                          endpoint returns 2xx to Paystack.
     */
    public function handlePaystackWebhook(string $rawBody, ?string $signature): array
    {
        if (! $this->paystack->verifyWebhookSignature($rawBody, $signature)) {
            return ['ack' => false, 'reason' => 'invalid_signature'];
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return ['ack' => false, 'reason' => 'unprocessable'];
        }

        if (($payload['event'] ?? null) !== 'charge.success') {
            return ['ack' => true, 'reason' => 'ignored_event'];
        }

        $data = $payload['data'] ?? [];
        $txRef = $data['reference'] ?? null;
        $userId = $this->parseUserId($txRef);
        $amount = round(((int) ($data['amount'] ?? 0)) / 100, 2);

        if (! $txRef || ! $userId || $amount <= 0) {
            return ['ack' => false, 'reason' => 'unprocessable'];
        }

        $user = User::find($userId);

        if (! $user) {
            return ['ack' => false, 'reason' => 'unknown_user'];
        }

        $credited = $this->creditTopUp($user, $amount, $txRef, (string) ($data['id'] ?? ''));

        return ['ack' => true, 'reason' => $credited ? 'credited' : 'duplicate'];
    }

    private function parseUserId(?string $reference): ?int
    {
        if (preg_match('/^WR-(\d+)-/', (string) $reference, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
