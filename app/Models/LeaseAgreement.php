<?php

namespace App\Models;

use App\Enums\LeaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EV/fuel lease-to-own agreement (WORKRIDE-DESIGN-REVIEWS §4): the driver pays
 * per_km_ngn × trip distance from earnings until the vehicle is theirs. The
 * fuel baseline makes the charge a hedge — it only stacks up while fuel is
 * expensive enough to beat.
 */
class LeaseAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'driver_id',
        'total_ngn',
        'paid_ngn',
        'per_km_ngn',
        'fuel_baseline_ngn_per_litre',
        'status',
        'next_due_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_ngn' => 'decimal:2',
            'paid_ngn' => 'decimal:2',
            'per_km_ngn' => 'decimal:2',
            'fuel_baseline_ngn_per_litre' => 'decimal:2',
            'status' => LeaseStatus::class,
            'next_due_at' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function outstanding(): float
    {
        return max(0.0, round((float) $this->total_ngn - (float) $this->paid_ngn, 2));
    }

    public function progressPercent(): int
    {
        return $this->total_ngn > 0 ? (int) round(((float) $this->paid_ngn / (float) $this->total_ngn) * 100) : 0;
    }
}
