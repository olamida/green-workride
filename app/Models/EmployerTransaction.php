<?php

namespace App\Models;

use App\Enums\EmployerTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_wallet_id',
        'type',
        'amount',
        'reference',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => EmployerTransactionType::class,
            'meta' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(EmployerWallet::class, 'employer_wallet_id');
    }
}
