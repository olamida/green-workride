<?php

namespace App\Models;

use App\Enums\PermitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Regulatory/insurance permit (guide §15): "Staff Mobility Cooperative"
 * registration, commercial vehicle papers, insurance, safety certificates.
 */
class Permit extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'user_id',
        'permit_type',
        'permit_number',
        'issuer',
        'issued_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'permit_type' => PermitType::class,
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Expires within the given days (default 30) — used for the reminder list.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return ! $this->isExpired() && $this->expires_at->isBefore(now()->addDays($days));
    }
}
