<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per KYC attempt (Sprint 3.6). Drives the 5/day rate limit and the
 * audit trail the Control Tower needs to spot spoofing / brute-force patterns.
 */
class VerificationAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tier',
        'provider',
        'liveness_score',
        'status',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'liveness_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
