<?php

namespace App\Services;

use App\Enums\VerificationProvider;
use App\Enums\VerificationTier;
use App\Models\ApiCostLog;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\Storage;

/**
 * Tier 3 — driver verification with commercial anti-spoofing (Smile Identity
 * SmartSelfie). True liveness (depth, moiré, reflection, rPPG) cannot be faked
 * with open-source tooling, so this tier uses a licensed vendor like the guide
 * mandates for anyone who carries passengers or withdraws >₦10k.
 *
 * The flow: the PWA runs the Smile SDK, posts the document + selfie, then Smile
 * calls our webhook with the result. We only trust a signature-verified result;
 * the anti-spoof score must clear the configured threshold.
 */
class SmileIdService
{
    public function __construct(private VerificationService $verifications) {}

    /**
     * Start a driver verification. Creates the Level-3 record (pending) so the
     * Control Tower can track it before the Smile webhook resolves it.
     */
    public function start(User $user, string $idCardPath, ?string $selfiePath = null): Verification
    {
        $config = config('services.smile');
        $enabled = (bool) ($config['enabled'] ?? false);

        return $user->verifications()->updateOrCreate(
            ['type' => 'driver'],
            [
                'document_hash' => hash('sha256', (string) file_get_contents(
                    Storage::disk('private')->path($idCardPath)
                )),
                'provider' => VerificationProvider::Smile,
                'tier' => VerificationTier::Tier3,
                'selfie_path' => $selfiePath,
                'selfie_retention_expires_at' => $selfiePath
                    ? now()->addDays((int) config('workride.verification.selfie_retention_days', 30))
                    : null,
                'status' => 'pending',
                'admin_note' => $enabled
                    ? 'Driver liveness job started — awaiting Smile result.'
                    : 'Driver verification requires manual review.',
                'verified_by' => null,
                'verified_at' => null,
            ],
        );
    }

    /**
     * Handle the Smile webhook. Signature (HMAC-SHA256) is the only gate.
     * Returns ['ack' => bool, 'reason' => string].
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        $secret = config('services.smile.webhook_secret');

        if (! $secret) {
            return ['ack' => false, 'reason' => 'smile_not_configured'];
        }

        $expected = hash_hmac('sha256', $payload, (string) $secret);

        if (! hash_equals($expected, $signature)) {
            return ['ack' => false, 'reason' => 'invalid_signature'];
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || empty($data['user_id'])) {
            return ['ack' => false, 'reason' => 'malformed_payload'];
        }

        $user = User::find($data['user_id']);

        if (! $user) {
            return ['ack' => false, 'reason' => 'unknown_user'];
        }

        $spoofScore = (int) ($data['anti_spoof_score'] ?? 0);
        $threshold = (int) config('services.smile.anti_spoof_threshold', 80);
        $resultCode = (int) ($data['result_code'] ?? 999);
        $passes = $resultCode === 0 && $spoofScore >= $threshold;

        $verification = $user->verifications()->updateOrCreate(
            ['type' => 'driver'],
            [
                'liveness_score' => $spoofScore,
                'provider' => VerificationProvider::Smile,
                'tier' => VerificationTier::Tier3,
                'status' => $passes ? 'approved' : 'rejected',
                'admin_note' => $passes
                    ? 'Smile SmartSelfie anti-spoof passed.'
                    : 'Smile anti-spoof check failed (score below threshold).',
                'verified_by' => null,
                'verified_at' => $passes ? now() : null,
            ],
        );

        if ($passes) {
            $this->verifications->recomputeLevel($user);
        }

        $this->logCost($user->id, (float) (config('services.smile.cost_naira') ?? 400), 'SMILE-'.$user->id.'-'.$verification->id);

        return ['ack' => true, 'reason' => $passes ? 'approved' : 'rejected'];
    }

    private function logCost(int $userId, float $cost, string $reference): void
    {
        ApiCostLog::create([
            'provider' => 'smile',
            'service' => 'driver_liveness',
            'cost_naira' => round($cost, 2),
            'user_id' => $userId,
            'purpose' => 'driver_liveness',
            'reference' => $reference,
        ]);
    }
}
