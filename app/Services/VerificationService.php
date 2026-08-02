<?php

namespace App\Services;

use App\Enums\VerificationProvider;
use App\Enums\VerificationTier;
use App\Http\Exceptions\VerificationThrottledException;
use App\Models\User;
use App\Models\Verification;
use App\Models\VerificationAttempt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * NDPR-compliant verification.
 *
 * Raw NIN is never stored — only a SHA256 hash plus the last 4 digits.
 * Workplace ID documents are stored as file hashes + original on disk.
 * Sprint 3.6 adds the tiered flow: open liveness (Tier 1), licensed NIMC
 * lookup (Tier 2) and commercial anti-spoof (Tier 3) — see the guide §7.
 */
class VerificationService
{
    public function hashNin(string $nin): array
    {
        $nin = preg_replace('/\D/', '', $nin);

        return [
            'nin_hash' => hash('sha256', $nin),
            'nin_last4' => Str::substr($nin, -4),
        ];
    }

    public function hashDocument(string $path): string
    {
        return hash('sha256', Storage::disk('public')->get($path));
    }

    public function submitWorkplace(User $user, int $workplaceId, ?string $documentPath = null): Verification
    {
        return $user->verifications()->updateOrCreate(
            ['type' => 'workplace_id'],
            [
                'workplace_id' => $workplaceId,
                'document_hash' => $documentPath ? $this->hashDocument($documentPath) : null,
                'status' => 'pending',
                'admin_note' => null,
                'verified_by' => null,
                'verified_at' => null,
            ],
        );
    }

    public function submitNin(User $user, string $nin): Verification
    {
        $hash = $this->hashNin($nin);

        return $user->verifications()->updateOrCreate(
            ['type' => 'nin'],
            [
                'nin_last4' => $hash['nin_last4'],
                'document_hash' => $hash['nin_hash'],
                'status' => 'pending',
                'admin_note' => null,
                'verified_by' => null,
                'verified_at' => null,
            ],
        );
    }

    /**
     * Tier 1 — staff ID via open-source liveness (free, 80% of users).
     * Liveness is a signal, not a gate: a pass auto-approves Level 1 (worst
     * case is a free ride, acceptable); a low score drops to manual review.
     */
    public function submitTier1(User $user, int $workplaceId, int $livenessScore, ?string $selfiePath): Verification
    {
        $minScore = (int) config('workride.verification.liveness_min_score', 75);
        $passes = $livenessScore >= $minScore;

        $verification = $user->verifications()->updateOrCreate(
            ['type' => 'workplace_id'],
            [
                'workplace_id' => $workplaceId,
                'liveness_score' => $livenessScore,
                'face_match_score' => null,
                'provider' => VerificationProvider::Open,
                'tier' => VerificationTier::Tier1,
                'selfie_path' => $selfiePath,
                'selfie_retention_expires_at' => $selfiePath
                    ? now()->addDays((int) config('workride.verification.selfie_retention_days', 30))
                    : null,
                'status' => $passes ? 'approved' : 'pending_manual_review',
                'admin_note' => $passes
                    ? 'Auto-approved: Tier 1 open liveness passed. Selfie kept for audit.'
                    : 'Auto-flagged: liveness below threshold. Manual review required.',
                'verified_by' => null,
                'verified_at' => $passes ? now() : null,
            ],
        );

        if ($passes) {
            $this->recomputeLevel($user);
        }

        return $verification;
    }

    /**
     * Encrypt a base64 selfie onto the private disk. Returns the file path.
     */
    public function storeSelfie(string $base64): string
    {
        $data = base64_decode(preg_replace('/^data:image\/[a-z+]+;base64,/', '', $base64), true);

        if ($data === false || $data === '') {
            throw new \InvalidArgumentException('Invalid selfie image data.');
        }

        return $this->storeEncrypted($data, 'selfies');
    }

    /**
     * Encrypt an uploaded selfie file onto the private disk. Returns the path.
     */
    public function storeSelfieFile(UploadedFile $file): string
    {
        return $this->storeEncrypted((string) $file->get(), 'selfies');
    }

    /**
     * Encrypt raw bytes (Crypt, not storage-level) onto the private disk.
     * Biometric data is encrypted even at rest so a disk dump leaks nothing.
     */
    public function storeEncrypted(string $contents, string $subdir): string
    {
        $path = $subdir.'/'.Str::uuid()->toString().'.enc';
        Storage::disk('private')->put($path, Crypt::encryptString($contents));

        return $path;
    }

    /**
     * 5 attempts per tier per day (anti-brute-force). Throws 429 when exceeded.
     */
    public function assertWithinAttemptLimit(User $user, string $tier): void
    {
        $limit = (int) config('workride.verification.attempts_per_day', 5);
        $attempts = VerificationAttempt::where('user_id', $user->id)
            ->where('tier', $tier)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($attempts >= $limit) {
            throw new VerificationThrottledException(
                'Too many verification attempts today. Please try again tomorrow.'
            );
        }
    }

    public function recordAttempt(
        User $user,
        string $tier,
        string $provider,
        ?int $livenessScore,
        string $status,
        ?string $ip = null,
    ): VerificationAttempt {
        return VerificationAttempt::create([
            'user_id' => $user->id,
            'tier' => $tier,
            'provider' => $provider,
            'liveness_score' => $livenessScore,
            'status' => $status,
            'ip_address' => $ip,
        ]);
    }

    public function approve(Verification $verification, User $reviewer, ?string $note = null): void
    {
        $verification->update([
            'status' => 'approved',
            'admin_note' => $note,
            'verified_by' => $reviewer->id,
            'verified_at' => now(),
        ]);

        $this->recomputeLevel($verification->user);
    }

    public function reject(Verification $verification, User $reviewer, string $note): void
    {
        $verification->update([
            'status' => 'rejected',
            'admin_note' => $note,
            'verified_by' => $reviewer->id,
        ]);
    }

    /**
     * Recompute the user's verification_level from their approved verifications.
     *
     * Level 1 = workplace ID approved
     * Level 2 = NIN approved
     * Level 3 = driver docs approved
     */
    public function recomputeLevel(User $user): void
    {
        $approved = $user->verifications()->where('status', 'approved')->pluck('type')->all();

        $level = 0;

        if (in_array('workplace_id', $approved, true)) {
            $level = 1;
        }

        if (in_array('nin', $approved, true)) {
            $level = max($level, 2);
        }

        if (in_array('driver', $approved, true)) {
            $level = 3;
        }

        if ($level !== $user->verification_level->value) {
            $user->update(['verification_level' => $level]);
        }
    }
}
