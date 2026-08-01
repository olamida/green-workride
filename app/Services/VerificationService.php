<?php

namespace App\Services;

use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * NDPR-compliant verification.
 *
 * Raw NIN is never stored — only a SHA256 hash plus the last 4 digits.
 * Workplace ID documents are stored as file hashes + original on disk.
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
