<?php

namespace App\Models;

use App\Enums\P2pTransferStatus;
use App\Enums\P2pTransferType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P2pTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_wallet_id',
        'receiver_user_id',
        'amount',
        'fee',
        'type',
        'reference',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'type' => P2pTransferType::class,
            'status' => P2pTransferStatus::class,
            'meta' => 'array',
        ];
    }

    public function senderWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }
}
