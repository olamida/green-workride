<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cash_balance',
        'subsidy_credits',
        'cash_collected_log',
        'version',
    ];

    /**
     * Model-level defaults mirror the DB column defaults so a freshly created
     * wallet carries the same values in memory (optimistic locking relies on
     * `version` being present before the first mutation).
     */
    protected $attributes = [
        'cash_balance' => 0,
        'subsidy_credits' => 0,
        'cash_collected_log' => 0,
        'version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2',
            'subsidy_credits' => 'decimal:2',
            'cash_collected_log' => 'decimal:2',
            'version' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
