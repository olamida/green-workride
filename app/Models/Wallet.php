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
