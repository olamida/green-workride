<?php

namespace App\Models;

use App\Enums\CommodityCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tradeable commodity (gold gram, bag of rice/maize) or shop item whose
 * price is quoted in naira. Wallet balances buy positions; shop orders buy
 * physical goods via QR vouchers.
 */
class Commodity extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'category',
        'unit',
        'current_price_ngn',
        'is_tradable',
        'is_shop_item',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'category' => CommodityCategory::class,
            'current_price_ngn' => 'decimal:2',
            'is_tradable' => 'boolean',
            'is_shop_item' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(CommodityPosition::class);
    }

    public function unitLabel(): string
    {
        return match ($this->unit) {
            'gram' => 'gram',
            'kg' => 'kg',
            'bag' => 'bag',
            'litre' => 'litre',
            default => $this->unit,
        };
    }
}
