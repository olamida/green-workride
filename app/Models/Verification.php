<?php

namespace App\Models;

use App\Enums\VerificationProvider;
use App\Enums\VerificationTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'workplace_id',
        'document_hash',
        'nin_last4',
        'status',
        'admin_note',
        'verified_by',
        'verified_at',
        'liveness_score',
        'face_match_score',
        'provider',
        'tier',
        'nimc_reference',
        'selfie_path',
        'selfie_retention_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'liveness_score' => 'integer',
            'face_match_score' => 'integer',
            'provider' => VerificationProvider::class,
            'tier' => VerificationTier::class,
            'selfie_retention_expires_at' => 'datetime',
        ];
    }

    /**
     * Decrypt the stored selfie for a reviewer. Returns raw image bytes or null.
     */
    public function decryptedSelfie(): ?string
    {
        if (! $this->selfie_path) {
            return null;
        }

        $contents = Storage::disk('private')->get($this->selfie_path);

        return $contents ? Crypt::decryptString($contents) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
