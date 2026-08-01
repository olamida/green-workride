<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prepaid corporate billing wallet. Optimistic locking mirrors WalletService.
 * Every mutation writes an idempotent employer_transactions row for the
 * MDA/government audit trail (guide §2.2 #4).
 */
class EmployerWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'cash_balance',
        'version',
    ];

    protected $attributes = [
        'cash_balance' => 0,
        'version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2',
            'version' => 'integer',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EmployerTransaction::class);
    }
}
