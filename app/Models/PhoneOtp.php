<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One-time passcode for phone verification. The token is stored hashed
 * (SHA-256), never plaintext, and consumed after a single successful check.
 */
class PhoneOtp extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'purpose',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isConsumed();
    }

    public function matches(string $plain): bool
    {
        return hash_equals($this->token_hash, hash('sha256', $plain));
    }

    public function recordAttempt(): void
    {
        $this->increment('attempts');
    }

    public function consume(): void
    {
        $this->update(['consumed_at' => now()]);
    }
}
