<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * NURTW/RTEAN chapter + park. Don't fight the unions — make them agents:
 * their park is the official hub and they receive a per-trip remittance.
 */
class Union extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'park_location',
        'lat',
        'lng',
        'corridor',
        'commission_rate',
        'contact_name',
        'contact_phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function remittances(): HasMany
    {
        return $this->hasMany(StakeholderRemittance::class);
    }

    public function totalPending(): float
    {
        return (float) $this->remittances()->where('status', 'pending')->sum('amount');
    }
}
