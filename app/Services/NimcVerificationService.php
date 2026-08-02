<?php

namespace App\Services;

use App\Enums\VerificationProvider;
use App\Enums\VerificationTier;
use App\Models\ApiCostLog;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\Http;

/**
 * Tier 2 — NIN verification through a NIMC-licensed partner (IdentityPass).
 *
 * NDPR design (guide §15):
 * - The raw NIN is relayed to the licensed partner over TLS and NEVER persisted.
 * - We store only SHA-256 hash + last 4 + the partner's reference.
 * - Every paid lookup is logged to api_cost_logs (user_id, purpose, reference)
 *   and refused once the global OR per-provider monthly cap is spent.
 * - Idempotency: re-submitting the SAME NIN for a user after a resolved result
 *   does not trigger a second paid call.
 * - Fail-safe: provider down / unconfigured / cap-exhausted → manual review,
 *   never a silent pass or a 500.
 */
class NimcVerificationService
{
    public function __construct(
        private VerificationService $verifications,
        private CostLogger $costLogger,
    ) {}

    public function verify(User $user, string $nin, ?int $livenessScore, ?string $selfiePath = null): Verification
    {
        $hash = $this->verifications->hashNin($nin);
        $existing = $user->verifications()->where('type', 'nin')->first();

        // Idempotent — same NIN already resolved by a provider: don't re-pay.
        if (
            $existing
            && $existing->document_hash === $hash['nin_hash']
            && in_array($existing->status, ['approved', 'rejected', 'pending_manual_review'], true)
        ) {
            return $existing;
        }

        $config = config('services.identitypass');
        $enabled = (bool) ($config['enabled'] ?? false);
        $cost = (float) ($config['cost_naira'] ?? 100);
        $provider = VerificationProvider::IdentityPass;

        if (! $enabled) {
            return $this->resolve($user, $hash, $livenessScore, $selfiePath, $provider, 'pending_manual_review',
                'NIMC provider not configured — manual review required.');
        }

        if (! $this->costLogger->withinMonthlyCap($cost) || $this->providerSpend('identitypass') + $cost > (float) ($config['monthly_cap_naira'] ?? 50000)) {
            return $this->resolve($user, $hash, $livenessScore, $selfiePath, $provider, 'pending_manual_review',
                'NIMC monthly budget reached — manual review required.');
        }

        $reference = 'NIN-'.$user->id.'-'.now()->format('YmdHis').'-'.substr($hash['nin_hash'], 0, 8);

        try {
            $response = Http::timeout(15)
                ->withToken((string) $config['key'])
                ->post(rtrim((string) $config['base_url'], '/').$config['nin_endpoint'], [
                    'number' => $nin,
                ]);

            $this->logCost($user->id, $cost, $reference);
        } catch (\Throwable $e) {
            logger()->warning('IdentityPass NIN check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->resolve($user, $hash, $livenessScore, $selfiePath, $provider, 'pending_manual_review',
                'NIMC provider unreachable — manual review required.');
        }

        $body = $response->json() ?? [];

        if ($response->failed() || empty($body['status'])) {
            return $this->resolve($user, $hash, $livenessScore, $selfiePath, $provider, 'rejected',
                'NIN not found in the NIMC registry.');
        }

        // Face matching against the official NIMC photo is intentionally NOT run
        // at MVP: the official photo is biometric PII with strict purpose
        // limitation. Liveness stays the primary signal; admin can re-check.
        $nimcRef = $body['data']['nimc_ref'] ?? $reference;

        return $this->resolve($user, $hash, $livenessScore, $selfiePath, $provider, 'approved',
            'NIN confirmed via NIMC-licensed partner.', $nimcRef);
    }

    /**
     * Naira spent with this provider this calendar month (for the per-provider cap).
     */
    public function providerSpend(string $provider): float
    {
        return (float) ApiCostLog::where('provider', $provider)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cost_naira');
    }

    private function resolve(
        User $user,
        array $hash,
        ?int $livenessScore,
        ?string $selfiePath,
        VerificationProvider $provider,
        string $status,
        ?string $note,
        ?string $nimcRef = null,
    ): Verification {
        $verification = $user->verifications()->updateOrCreate(
            ['type' => 'nin'],
            [
                'nin_last4' => $hash['nin_last4'],
                'document_hash' => $hash['nin_hash'],
                'liveness_score' => $livenessScore,
                'provider' => $provider,
                'tier' => VerificationTier::Tier2,
                'nimc_reference' => $nimcRef,
                'selfie_path' => $selfiePath,
                'selfie_retention_expires_at' => $selfiePath
                    ? now()->addDays((int) config('workride.verification.selfie_retention_days', 30))
                    : null,
                'status' => $status,
                'admin_note' => $note,
                'verified_by' => null,
                'verified_at' => $status === 'approved' ? now() : null,
            ],
        );

        if ($status === 'approved') {
            $this->verifications->recomputeLevel($user);
        }

        return $verification;
    }

    private function logCost(int $userId, float $cost, string $reference): void
    {
        ApiCostLog::create([
            'provider' => 'identitypass',
            'service' => 'nin_check',
            'cost_naira' => round($cost, 2),
            'user_id' => $userId,
            'purpose' => 'nin_verification',
            'reference' => $reference,
        ]);
    }
}
