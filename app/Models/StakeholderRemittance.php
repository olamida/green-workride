<?php

namespace App\Models;

use App\Enums\RemittanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-trip union share (fare × union commission). Created at trip completion,
 * settled by the daily Moniepoint run — never fight the unions, pay them.
 */
class StakeholderRemittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'union_id',
        'amount',
        'status',
        'reference',
        'paid_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RemittanceStatus::class,
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function union(): BelongsTo
    {
        return $this->belongsTo(Union::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function markPaid(): void
    {
        if ($this->status === RemittanceStatus::Pending) {
            $this->update(['status' => RemittanceStatus::Paid, 'paid_at' => now()]);
        }
    }
}
