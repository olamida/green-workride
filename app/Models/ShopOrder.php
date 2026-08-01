<?php

namespace App\Models;

use App\Enums\OrderPaymentSource;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A wallet-paid shop order for physical commodities (rice, maize, fuel
 * vouchers). Orders are collected at partner outlets via a QR voucher.
 */
class ShopOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'items',
        'total_ngn',
        'paid_from',
        'status',
        'meta',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_ngn' => 'decimal:2',
            'paid_from' => OrderPaymentSource::class,
            'status' => OrderStatus::class,
            'meta' => 'array',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
