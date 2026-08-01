<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's holding in a commodity. avg_cost_ngn is the quantity-weighted
 * average cost per unit; realized P&L is computed on sell.
 */
class CommodityPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commodity_id',
        'quantity',
        'avg_cost_ngn',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'avg_cost_ngn' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function currentValue(): float
    {
        return round((float) $this->quantity * (float) $this->commodity->current_price_ngn, 2);
    }
}
