<?php

namespace App\Models;

use App\Enums\TrustLedgerDirection;
use App\Enums\TrustLedgerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row in the Community Trust ledger (guide §2.1). Every movement carries
 * an idempotent reference and a running balance so the 15% profit share and
 * the Time-Bank float are auditable end-to-end.
 */
class CommunityTrust extends Model
{
    use HasFactory;

    protected $table = 'community_trust';

    protected $fillable = [
        'direction',
        'type',
        'amount',
        'balance_after',
        'reference',
        'meta',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => TrustLedgerDirection::class,
            'type' => TrustLedgerType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'meta' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
